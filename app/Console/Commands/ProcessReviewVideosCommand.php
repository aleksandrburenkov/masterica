<?php

namespace App\Console\Commands;

use App\Enums\ReviewStatus;
use App\Enums\ReviewVideoStatus;
use App\Models\ReviewVideo;
use App\Services\ReviewVideoService;
use App\Services\VideoSecurityService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Обслуживание видеоотзывов: публикация одобренных видео, повторная проверка и
 * очистка карантина.
 *
 * Обычный сценарий (загрузка → модерация) работает без этой команды. Она нужна
 * как страховка: если видео было одобрено, но по какой-то причине не
 * опубликовалось, либо для обслуживания карантина.
 */
class ProcessReviewVideosCommand extends Command
{
    protected $signature = 'videos:process
        {--limit=100 : Сколько записей обработать за запуск}
        {--prune-quarantine : Удалить изолированные файлы старше срока хранения}
        {--rescan-quarantine : Повторно проверить изолированные файлы и вернуть чистые}
        {--days=30 : Срок хранения изолированных файлов, дней}';

    protected $description = 'Публикует одобренные видеоотзывы и обслуживает карантин';

    public function handle(ReviewVideoService $service, VideoSecurityService $security): int
    {
        if ($this->option('prune-quarantine')) {
            return $this->pruneQuarantine();
        }

        if ($this->option('rescan-quarantine')) {
            return $this->rescanQuarantine($service, $security);
        }

        return $this->publishApproved($service);
    }

    /**
     * Публикует видео отзывов, которые уже одобрены модератором, но ещё не
     * обработаны (страховка на случай сбоя при модерации).
     */
    private function publishApproved(ReviewVideoService $service): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $videos = ReviewVideo::query()
            ->where('status', ReviewVideoStatus::Pending->value)
            ->whereHas('review', fn ($query) => $query->where('status', ReviewStatus::Approved->value))
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($videos->isEmpty()) {
            $this->info('Нет видео, требующих публикации.');

            return self::SUCCESS;
        }

        foreach ($videos as $video) {
            $service->publish($video);
            $this->line('Опубликовано видео #'.$video->id.' ('.$video->refresh()->status->value.')');
        }

        $this->info('Готово: '.$videos->count().' записей.');

        return self::SUCCESS;
    }

    /**
     * Повторная проверка изолированных файлов: если эвристика обновилась и файл
     * теперь считается чистым, он возвращается в ожидание модерации (или сразу
     * публикуется, если отзыв уже одобрен).
     */
    private function rescanQuarantine(ReviewVideoService $service, VideoSecurityService $security): int
    {
        $restored = 0;
        $kept = 0;

        ReviewVideo::query()
            ->where('status', ReviewVideoStatus::Quarantined->value)
            ->cursor()
            ->each(function (ReviewVideo $video) use ($service, $security, &$restored, &$kept): void {
                $disk = Storage::disk($video->disk ?: (string) config('video.disk', 'local'));

                if (! is_string($video->path) || $video->path === '' || ! $disk->exists($video->path)) {
                    $kept++;

                    return;
                }

                $result = $security->scan($disk->path($video->path), $video->mime_type, $video->original_name);

                if (! $result->isClean()) {
                    $kept++;

                    return;
                }

                $incoming = trim((string) config('video.incoming_directory'), '/').'/'.basename($video->path);

                try {
                    if ($incoming !== $video->path) {
                        $disk->move($video->path, $incoming);
                    }
                } catch (Throwable $exception) {
                    report($exception);
                    $kept++;

                    return;
                }

                $video->forceFill([
                    'path' => $incoming,
                    'scan_status' => $result->status,
                    'scanner' => $result->scanner,
                    'scan_report' => $result->report,
                    'scanned_at' => now(),
                    'status' => ReviewVideoStatus::Pending,
                ])->save();

                if ($video->review?->status === ReviewStatus::Approved) {
                    $service->publish($video);
                }

                $restored++;
                $this->line('Возвращено из карантина видео #'.$video->id.'.');
            });

        $this->info('Возвращено из карантина: '.$restored.', осталось изолированными: '.$kept);

        return self::SUCCESS;
    }

    private function pruneQuarantine(): int
    {
        $days = max(1, (int) $this->option('days'));
        $threshold = now()->subDays($days)->getTimestamp();

        $deleted = 0;

        ReviewVideo::query()
            ->where('status', ReviewVideoStatus::Quarantined->value)
            ->cursor()
            ->each(function (ReviewVideo $video) use ($threshold, &$deleted): void {
                $disk = Storage::disk($video->disk ?: (string) config('video.disk', 'local'));

                if (! is_string($video->path) || $video->path === '' || ! $disk->exists($video->path)) {
                    return;
                }

                try {
                    if ($disk->lastModified($video->path) > $threshold) {
                        return;
                    }

                    $disk->delete($video->path);
                    $video->forceFill(['path' => null])->save();
                    $deleted++;
                } catch (Throwable $exception) {
                    report($exception);
                }
            });

        $this->info('Удалено изолированных файлов: '.$deleted);

        return self::SUCCESS;
    }
}
