<?php

namespace App\Observers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;

/**
 * Keeps stock in sync with the order lifecycle no matter how an order changes:
 * through the storefront services, the admin panel status field, a refund or a
 * deletion (single or bulk). Stock operations are idempotent, so relying on the
 * observer instead of the call sites is safe.
 */
class OrderObserver
{
    public function updated(Order $order): void
    {
        if (! $order->wasChanged('status')) {
            return;
        }

        app(OrderService::class)->reconcileStock(
            $order,
            self::statusValue($order->getOriginal('status')),
            self::statusValue($order->getChanges()['status'] ?? $order->status),
        );
    }

    public function deleting(Order $order): void
    {
        app(OrderService::class)->reconcileDeletion($order);
    }

    private static function statusValue(mixed $status): ?string
    {
        if ($status instanceof OrderStatus) {
            return $status->value;
        }

        return $status === null ? null : (string) $status;
    }
}
