<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Throwable;

/**
 * Работа с видеофайлами через ffmpeg/ffprobe.
 *
 * Все внешние вызовы необязательны: если бинарник недоступен, методы
 * корректно сообщают об этом, а приложение продолжает работать, сохраняя
 * исходный файл без перекодирования.
 */
class VideoProcessingService
{
    /** @var array<string, bool> */
    private static array $availability = [];

    public function ffmpegAvailable(): bool
    {
        return $this->binaryAvailable((string) config('video.ffmpeg_path', 'ffmpeg'));
    }

    public function ffprobeAvailable(): bool
    {
        return $this->binaryAvailable((string) config('video.ffprobe_path', 'ffprobe'));
    }

    /**
     * Метаданные видео: длительность, ширина и высота.
     *
     * @return array{duration?: int, width?: int, height?: int}
     */
    public function probe(string $absolutePath): array
    {
        if (! $this->ffprobeAvailable() || ! is_file($absolutePath)) {
            return [];
        }

        $binary = (string) config('video.ffprobe_path', 'ffprobe');

        $process = $this->run([
            $binary, '-v', 'error',
            '-select_streams', 'v:0',
            '-show_entries', 'stream=width,height',
            '-show_entries', 'format=duration',
            '-of', 'json',
            $absolutePath,
        ]);

        if ($process === null || ! $process->isSuccessful()) {
            return [];
        }

        $json = json_decode(trim($process->getOutput()), true);

        if (! is_array($json)) {
            return [];
        }

        $stream = (array) ($json['streams'][0] ?? []);
        $format = (array) ($json['format'] ?? []);

        $metadata = [];

        if (isset($format['duration']) && is_numeric($format['duration'])) {
            $metadata['duration'] = (int) round((float) $format['duration']);
        }

        if (isset($stream['width']) && is_numeric($stream['width'])) {
            $metadata['width'] = (int) $stream['width'];
        }

        if (isset($stream['height']) && is_numeric($stream['height'])) {
            $metadata['height'] = (int) $stream['height'];
        }

        return $metadata;
    }

    /**
     * Перекодирует видео в H.264/AAC с оптимизацией под веб (faststart).
     */
    public function transcode(string $source, string $target): bool
    {
        if (! $this->ffmpegAvailable() || ! is_file($source)) {
            return false;
        }

        $maxWidth = max(320, (int) config('video.compression.max_width', 1280));
        $crf = max(0, min(51, (int) config('video.compression.crf', 28)));
        $preset = (string) config('video.compression.preset', 'veryfast');
        $audio = (string) config('video.compression.audio_bitrate', '96k');

        $process = $this->run([
            (string) config('video.ffmpeg_path', 'ffmpeg'),
            '-y', '-hide_banner', '-loglevel', 'error',
            '-i', $source,
            '-vf', "scale='min({$maxWidth},iw)':-2",
            '-c:v', 'libx264',
            '-crf', (string) $crf,
            '-preset', $preset,
            '-pix_fmt', 'yuv420p',
            '-c:a', 'aac',
            '-b:a', $audio,
            '-movflags', '+faststart',
            '-max_muxing_queue_size', '1024',
            $target,
        ], (int) config('video.process_timeout', 900));

        return $process !== null && $process->isSuccessful() && is_file($target) && filesize($target) > 0;
    }

    /**
     * Извлекает кадр-обложку из видео.
     */
    public function extractPoster(string $source, string $target, ?int $atSeconds = null): bool
    {
        if (! $this->ffmpegAvailable() || ! is_file($source)) {
            return false;
        }

        $width = max(160, (int) config('video.poster.width', 720));
        $at = max(0, $atSeconds ?? (int) config('video.poster.at_seconds', 1));

        $process = $this->run([
            (string) config('video.ffmpeg_path', 'ffmpeg'),
            '-y', '-hide_banner', '-loglevel', 'error',
            '-ss', (string) $at,
            '-i', $source,
            '-frames:v', '1',
            '-vf', "scale={$width}:-2",
            $target,
        ], (int) config('video.process_timeout', 900));

        return $process !== null && $process->isSuccessful() && is_file($target) && filesize($target) > 0;
    }

    /**
     * @param  list<string>  $command
     */
    private function run(array $command, int $timeout = 60): ?Process
    {
        try {
            $process = new Process($command);
            $process->setTimeout($timeout);
            $process->run();

            return $process;
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    private function binaryAvailable(string $binary): bool
    {
        $binary = trim($binary);

        if ($binary === '') {
            return false;
        }

        if (array_key_exists($binary, self::$availability)) {
            return self::$availability[$binary];
        }

        try {
            $process = new Process([$binary, '-version']);
            $process->setTimeout(10);
            $process->run();
            $available = $process->isSuccessful();
        } catch (Throwable) {
            $available = false;
        }

        return self::$availability[$binary] = $available;
    }
}
