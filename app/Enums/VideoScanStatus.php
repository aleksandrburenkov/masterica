<?php

namespace App\Enums;

enum VideoScanStatus: string
{
    case Pending = 'pending';
    case Clean = 'clean';
    case Suspicious = 'suspicious';
    case Infected = 'infected';
    case Error = 'error';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Не проверено',
            self::Clean => 'Чисто',
            self::Suspicious => 'Подозрительно',
            self::Infected => 'Угроза',
            self::Error => 'Ошибка проверки',
        };
    }

    /** Файл можно отдавать посетителю только после успешной проверки. */
    public function isPlayable(): bool
    {
        return $this === self::Clean;
    }

    public function isThreat(): bool
    {
        return $this === self::Suspicious || $this === self::Infected;
    }
}
