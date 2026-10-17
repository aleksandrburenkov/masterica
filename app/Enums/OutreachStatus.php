<?php

namespace App\Enums;

enum OutreachStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Confirmed = 'confirmed';
    case Declined = 'declined';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Новая',
            self::Contacted => 'Связались',
            self::Confirmed => 'Подтверждена',
            self::Declined => 'Отклонена',
            self::Completed => 'Выполнена',
        };
    }
}
