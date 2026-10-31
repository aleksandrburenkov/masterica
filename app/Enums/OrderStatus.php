<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Draft = 'draft';
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case Processing = 'processing';
    case Ready = 'ready';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Черновик',
            self::PendingPayment => 'Ожидает оплаты',
            self::Paid => 'Оплачен',
            self::Processing => 'Обрабатывается',
            self::Ready => 'Готов к выдаче',
            self::Completed => 'Выполнен',
            self::Cancelled => 'Отменён',
            self::Refunded => 'Возврат',
        };
    }

    /**
     * A live order still holds a reservation on stock (on-hand minus reserved).
     * Completing the order consumes the reservation; cancelling or refunding it
     * releases the reservation.
     */
    public function isOpen(): bool
    {
        return in_array($this, [
            self::Draft,
            self::PendingPayment,
            self::Paid,
            self::Processing,
            self::Ready,
        ], true);
    }
}
