<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Receipt = 'receipt';
    case Reserve = 'reserve';
    case Release = 'release';
    case Writeoff = 'writeoff';
    case Adjustment = 'adjustment';
    case Return = 'return';

    public function label(): string
    {
        return match ($this) {
            self::Receipt => 'Поступление',
            self::Reserve => 'Резерв',
            self::Release => 'Освобождение резерва',
            self::Writeoff => 'Списание',
            self::Adjustment => 'Корректировка',
            self::Return => 'Возврат',
        };
    }

    /** Movements that change the physical on-hand figure. */
    public function isPhysical(): bool
    {
        return in_array($this, [
            self::Receipt,
            self::Writeoff,
            self::Adjustment,
            self::Return,
        ], true);
    }
}
