<?php

namespace App\Enums;

enum ReviewVideoStatus: string
{
    // Загружено и проверено, ожидает решения модератора.
    case Pending = 'pending';

    // Одобрено модератором: файл опубликован и доступен в карточке отзыва.
    case Ready = 'ready';

    case Failed = 'failed';

    case Quarantined = 'quarantined';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Ожидает модерации',
            self::Ready => 'Опубликовано',
            self::Failed => 'Ошибка обработки',
            self::Quarantined => 'Изолировано',
        };
    }

    public function isReady(): bool
    {
        return $this === self::Ready;
    }

    public function isQuarantined(): bool
    {
        return $this === self::Quarantined;
    }
}
