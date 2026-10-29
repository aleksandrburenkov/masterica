<?php

namespace App\Support;

use App\Enums\VideoScanStatus;

/**
 * Результат проверки загруженного видео на вредоносные вложения.
 */
final class VideoScanResult
{
    public function __construct(
        public readonly VideoScanStatus $status,
        public readonly string $scanner,
        public readonly ?string $report = null,
    ) {}

    public static function clean(string $scanner, ?string $report = null): self
    {
        return new self(VideoScanStatus::Clean, $scanner, $report);
    }

    public function isClean(): bool
    {
        return $this->status === VideoScanStatus::Clean;
    }

    public function isThreat(): bool
    {
        return $this->status->isThreat();
    }

    public function isPlayable(): bool
    {
        return $this->status->isPlayable();
    }
}
