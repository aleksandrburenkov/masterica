<?php

namespace App\Enums;

enum ReviewType: string
{
    case Text = 'text';
    case Video = 'video';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Текстовый',
            self::Video => 'Видео',
        };
    }
}
