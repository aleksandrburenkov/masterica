<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Receipt = 'receipt';
    case Reserve = 'reserve';
    case Release = 'release';
    case Writeoff = 'writeoff';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Receipt => 'Поступление',
            self::Reserve => 'Резерв',
            self::Release => 'Освобождение резерва',
            self::Writeoff => 'Списание',
            self::Adjustment => 'Корректировка',
        };
    }
}
