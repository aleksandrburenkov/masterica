<?php

namespace App\Models;

use App\Enums\ReviewVideoStatus;
use App\Enums\VideoScanStatus;
use App\Services\ReviewVideoService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

#[Fillable([
    'review_id', 'disk', 'path', 'original_name', 'extension', 'mime_type',
    'size_bytes', 'processed_size_bytes', 'checksum', 'width', 'height',
    'duration_seconds', 'poster_path', 'status', 'scan_status', 'scanner',
    'scan_report', 'scanned_at', 'processed_at',
])]
class ReviewVideo extends Model
{
    protected function casts(): array
    {
        return [
            'status' => ReviewVideoStatus::class,
            'scan_status' => VideoScanStatus::class,
            'size_bytes' => 'integer',
            'processed_size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'duration_seconds' => 'integer',
            'scanned_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleted(function (ReviewVideo $video): void {
            $cleanup = fn () => app(ReviewVideoService::class)->purgeFiles($video);

            if (DB::transactionLevel() > 0) {
                DB::afterCommit($cleanup);

                return;
            }

            $cleanup();
        });
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    /**
     * Файл можно показывать после успешной проверки на вредоносные вложения.
     * Используется для доступа к файлу (автору и сотрудникам доступно и
     * неопубликованное видео — например, для предпросмотра модератором).
     */
    public function isPlayable(): bool
    {
        return $this->scan_status === VideoScanStatus::Clean
            && ! $this->status?->isQuarantined()
            && is_string($this->path)
            && $this->path !== '';
    }

    /** Видео одобрено модератором и показано в публичной карточке отзыва. */
    public function isReady(): bool
    {
        return $this->status?->isReady() === true && $this->isPlayable();
    }

    public function streamUrl(): ?string
    {
        if (! $this->isPlayable()) {
            return null;
        }

        return route('reviews.video', ['review' => $this->review_id]);
    }

    public function posterUrl(): ?string
    {
        if (! $this->poster_path) {
            return null;
        }

        return route('reviews.poster', ['review' => $this->review_id]);
    }

    public function humanSize(): string
    {
        $bytes = $this->processed_size_bytes ?: $this->size_bytes;

        if (! $bytes) {
            return '—';
        }

        $units = ['Б', 'КБ', 'МБ', 'ГБ'];
        $value = (float) $bytes;
        $unit = 0;

        while ($value >= 1024 && $unit < count($units) - 1) {
            $value /= 1024;
            $unit++;
        }

        return ($unit === 0 ? (string) (int) $value : number_format($value, 1, ',', ' ')).' '.$units[$unit];
    }

    public function durationLabel(): string
    {
        if (! $this->duration_seconds) {
            return '—';
        }

        $seconds = (int) $this->duration_seconds;
        $minutes = intdiv($seconds, 60);
        $rest = $seconds % 60;

        return sprintf('%d:%02d', $minutes, $rest);
    }

    public function resolutionLabel(): string
    {
        if (! $this->width || ! $this->height) {
            return '—';
        }

        return $this->width.'×'.$this->height;
    }
}
