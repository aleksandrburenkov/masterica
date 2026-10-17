<?php

namespace App\Enums;

enum ReviewStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'На модерации',
            self::Approved => 'Опубликован',
            self::Rejected => 'Отклонён',
        };
    }
}
