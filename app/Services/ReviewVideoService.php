<?php

namespace App\Services;

use App\Enums\ReviewVideoStatus;
use App\Models\Review;
use App\Models\ReviewVideo;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Приём, проверка, хранение и публикация видеофайлов отзывов.
 *
 * Сценарий намеренно простой и без очереди: файл проверяется и сохраняется в
 * каталог ожидания, затем ожидает решения модератора. При одобрении файл
 * переезжает в каталог видео и — только если он крупный — перекодируется.
 * При отклонении файл удаляется, а текстовый отзыв остаётся.
 */
class ReviewVideoService
{
    public function __construct(
        private readonly VideoSecurityService $security,
        private readonly VideoProcessingService $processing,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Сохраняет проверенный файл в каталог ожидания модерации.
     */
    public function store(Review $review, UploadedFile $file, ?User $actor = null): ReviewVideo
    {
        $this->assertAllowed($file);

        $diskName = (string) config('video.disk', 'local');
        $disk = Storage::disk($diskName);

        $uuid = (string) Str::uuid();
        $extension = $this->extension($file);
        $incoming = trim((string) config('video.incoming_directory'), '/').'/'.$uuid.'.'.$extension;

        $disk->putFileAs(dirname($incoming), $file, basename($incoming));

        try {
            $absolute = $disk->path($incoming);
            $size = (int) (@filesize($absolute) ?: 0);
            $checksum = @hash_file('sha256', $absolute) ?: null;

            $scan = $this->security->scan($absolute, $file->getClientMimeType(), $file->getClientOriginalName());

            $video = $review->video()->updateOrCreate(
                ['review_id' => $review->id],
                [
                    'disk' => $diskName,
                    'path' => $incoming,
                    'original_name' => mb_substr(basename((string) $file->getClientOriginalName()), 0, 255),
                    'extension' => $extension,
                    'mime_type' => $this->canonicalMime($extension),
                    'size_bytes' => $size ?: null,
                    'checksum' => $checksum,
                    'status' => ReviewVideoStatus::Pending,
                    'scan_status' => $scan->status,
                    'scanner' => $scan->scanner,
                    'scan_report' => $scan->report,
                    'scanned_at' => now(),
                ],
            );

            if ($scan->isThreat()) {
                $this->quarantine($disk, $video);
                $this->audit->log($actor, 'review_video.quarantined', $video, 'Вредоносное или подозрительное вложение', [
                    'scan_status' => $scan->status->value,
                    'report' => $scan->report,
                ]);

                return $video->refresh();
            }

            if (! $scan->isClean()) {
                $video->forceFill(['status' => ReviewVideoStatus::Failed])->save();
                $this->audit->log($actor, 'review_video.scan_failed', $video, 'Не удалось проверить видео', [
                    'scan_status' => $scan->status->value,
                ]);
            }

            return $video->refresh();
        } catch (Throwable $exception) {
            if ($disk->exists($incoming)) {
                $disk->delete($incoming);
            }

            throw $exception;
        }
    }

    /**
     * Публикует видео после одобрения модератором: переносит файл в каталог
     * видео, при необходимости сжимает и создаёт обложку.
     */
    public function publish(ReviewVideo $video): void
    {
        if ($video->status?->isReady() || ! $video->isPlayable()) {
            return;
        }

        $disk = Storage::disk($video->disk ?: (string) config('video.disk', 'local'));
        $relative = $video->path;

        if (! is_string($relative) || $relative === '' || ! $disk->exists($relative)) {
            $video->forceFill(['status' => ReviewVideoStatus::Failed])->save();

            return;
        }

        try {
            $metadata = $this->processing->probe($disk->path($relative));
            $base = pathinfo($relative, PATHINFO_FILENAME);

            $target = $this->relocate($disk, $relative, $base);
            $mime = $video->mime_type;

            if ($this->shouldCompress($video)) {
                $compressed = $this->videoDirectory().'/'.$base.'.mp4';
                $disk->makeDirectory(dirname($compressed));

                if ($this->processing->transcode($disk->path($target), $disk->path($compressed))) {
                    if ($compressed !== $target) {
                        $disk->delete($target);
                    }
                    $target = $compressed;
                    $mime = 'video/mp4';
                } elseif ($disk->exists($compressed)) {
                    $disk->delete($compressed);
                }
            }

            $poster = $this->createPoster($disk, $target, $base, $metadata['duration'] ?? null);

            $video->forceFill([
                'path' => $target,
                'extension' => pathinfo($target, PATHINFO_EXTENSION),
                'mime_type' => $mime ?: 'video/mp4',
                'duration_seconds' => $metadata['duration'] ?? null,
                'width' => $metadata['width'] ?? null,
                'height' => $metadata['height'] ?? null,
                'processed_size_bytes' => (int) ($disk->size($target) ?: 0) ?: null,
                'poster_path' => $poster,
                'status' => ReviewVideoStatus::Ready,
                'processed_at' => now(),
            ])->save();
        } catch (Throwable $exception) {
            $video->forceFill(['status' => ReviewVideoStatus::Failed])->save();

            report($exception);
        }
    }

    /**
     * Публикует видео отзыва, если оно есть. Возвращает видео после обработки.
     */
    public function publishForReview(Review $review): ?ReviewVideo
    {
        $video = $review->video()->first();

        if ($video === null) {
            return null;
        }

        $this->publish($video);

        return $video->refresh();
    }

    /**
     * Полностью удаляет видео отзыва (файлы и запись). Текстовый отзыв
     * остаётся без изменений.
     */
    public function deleteForReview(Review $review): void
    {
        $review->video()->first()?->delete();
    }

    /**
     * Удаляет файлы видео и постера с диска.
     */
    public function purgeFiles(ReviewVideo $video): void
    {
        $disk = Storage::disk($video->disk ?: (string) config('video.disk', 'local'));

        foreach ([$video->path, $video->poster_path] as $path) {
            if (! is_string($path) || $path === '') {
                continue;
            }

            try {
                if ($disk->exists($path)) {
                    $disk->delete($path);
                }
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }

    /**
     * Переносит файл из каталога ожидания в постоянный каталог видео.
     */
    private function relocate(mixed $disk, string $relative, string $base): string
    {
        $extension = strtolower(pathinfo($relative, PATHINFO_EXTENSION) ?: 'mp4');
        $final = $this->videoDirectory().'/'.$base.'.'.$extension;

        if ($final === $relative) {
            return $relative;
        }

        if ($disk->exists($final)) {
            $final = $this->videoDirectory().'/'.$base.'-'.Str::lower(Str::random(6)).'.'.$extension;
        }

        $disk->makeDirectory(dirname($final));

        return $disk->move($relative, $final) ? $final : $relative;
    }

    /**
     * @return array{duration?: int, width?: int, height?: int}
     */
    private function createPoster(mixed $disk, string $videoRelative, string $base, ?int $duration): ?string
    {
        if (! $this->canCreatePoster()) {
            return null;
        }

        $poster = $this->posterDirectory().'/'.$base.'.jpg';
        $disk->makeDirectory(dirname($poster));

        $at = $duration !== null && $duration > 3 ? max(1, intdiv($duration, 10)) : null;

        if ($this->processing->extractPoster($disk->path($videoRelative), $disk->path($poster), $at)) {
            return $poster;
        }

        if ($disk->exists($poster)) {
            $disk->delete($poster);
        }

        return null;
    }

    private function quarantine(mixed $disk, ReviewVideo $video): void
    {
        $source = $video->path;
        $status = ['status' => ReviewVideoStatus::Quarantined];

        if (is_string($source) && $source !== '' && $disk->exists($source)) {
            $target = trim((string) config('video.quarantine_directory'), '/').'/'.basename($source);

            try {
                $disk->move($source, $target);
                $status['path'] = $target;
            } catch (Throwable $exception) {
                report($exception);
                $disk->delete($source);
                $status['path'] = null;
            }
        }

        $video->forceFill($status)->save();
    }

    private function assertAllowed(UploadedFile $file): void
    {
        $maxKb = (int) config('video.max_size_kb', 204800);

        if ($file->getSize() > $maxKb * 1024) {
            throw ValidationException::withMessages([
                'video_file' => 'Видео больше допустимого размера ('.round($maxKb / 1024).' МБ).',
            ]);
        }
    }

    private function extension(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $extension = preg_replace('/[^a-z0-9]/', '', $extension) ?: '';
        $allowed = (array) config('video.allowed_extensions', []);

        if ($extension === '' || ! in_array($extension, $allowed, true)) {
            throw ValidationException::withMessages([
                'video_file' => 'Недопустимый формат видеофайла.',
            ]);
        }

        return $extension;
    }

    private function canonicalMime(string $extension): string
    {
        return match ($extension) {
            'mov' => 'video/quicktime',
            'webm' => 'video/webm',
            'mkv' => 'video/x-matroska',
            'avi' => 'video/x-msvideo',
            'ogv' => 'video/ogg',
            default => 'video/mp4',
        };
    }

    private function videoDirectory(): string
    {
        return trim((string) config('video.directory', 'reviews/videos'), '/').'/'.now()->format('Y/m');
    }

    private function posterDirectory(): string
    {
        return trim((string) config('video.poster_directory', 'reviews/posters'), '/').'/'.now()->format('Y/m');
    }

    /** Крупные файлы сжимаем, небольшие публикуем как есть. */
    private function shouldCompress(ReviewVideo $video): bool
    {
        if (! (bool) config('video.compression.enabled', true) || ! $this->processing->ffmpegAvailable()) {
            return false;
        }

        $sizeKb = (int) ceil(((int) ($video->size_bytes ?? 0)) / 1024);
        $trigger = (int) config('video.compression.trigger_size_kb', 25600);

        return $sizeKb >= $trigger;
    }

    private function canCreatePoster(): bool
    {
        return (bool) config('video.poster.enabled', true) && $this->processing->ffmpegAvailable();
    }
}
