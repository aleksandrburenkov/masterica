<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Paid = 'paid';
    case Failed = 'failed';
    case PartialRefund = 'partial_refund';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Не оплачено',
            self::Paid => 'Оплачено',
            self::Failed => 'Ошибка оплаты',
            self::PartialRefund => 'Частичный возврат',
            self::Refunded => 'Возврат',
        };
    }
}
