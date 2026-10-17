<?php

namespace App\Enums;

enum SessionStatus: string
{
    case Scheduled = 'scheduled';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Запланирован',
            self::Cancelled => 'Отменён',
        };
    }
}
