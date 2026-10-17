<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Held = 'held';
    case PendingPayment = 'pending_payment';
    case Confirmed = 'confirmed';
    case Attended = 'attended';
    case NoShow = 'no_show';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Held => 'Место удерживается',
            self::PendingPayment => 'Ожидает оплаты',
            self::Confirmed => 'Подтверждена',
            self::Attended => 'Посещена',
            self::NoShow => 'Неявка',
            self::Expired => 'Истекла',
            self::Cancelled => 'Отменена',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Held, self::PendingPayment, self::Confirmed], true);
    }

    /** Statuses that still occupy a seat in a session. */
    public function occupiesSeat(): bool
    {
        return in_array($this, [self::Held, self::PendingPayment, self::Confirmed, self::Attended], true);
    }
}
