<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case PendingPayment = 'pending_payment';
    case Active = 'active';
    case Completed = 'completed';
    case Expired = 'expired';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Ожидает оплаты',
            self::Active => 'Активен',
            self::Completed => 'Завершён',
            self::Expired => 'Истёк',
            self::Revoked => 'Отозван',
        };
    }

    public function givesAccess(): bool
    {
        return in_array($this, [self::Active, self::Completed], true);
    }
}
