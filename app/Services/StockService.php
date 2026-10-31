<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Append-only stock ledger. Corrections are recorded as compensating
 * movements instead of editing history.
 *
 * On-hand (`product_variants.stock`) is only changed by physical movements
 * (receipt, write-off, adjustment, return). Reservations are derived from the
 * statuses of live orders, so availability is always consistent with the
 * order lifecycle and never double-counted.
 */
class StockService
{
    public const OPEN_ORDER_STATUSES = ['draft', 'pending_payment', 'paid', 'processing', 'ready'];

    public function record(
        ProductVariant $variant,
        StockMovementType $type,
        int $quantity,
        ?string $reason = null,
        ?User $actor = null,
        ?Model $reference = null,
    ): StockMovement {
        return DB::transaction(function () use ($variant, $type, $quantity, $reason, $actor, $reference) {
            /** @var ProductVariant $locked */
            $locked = ProductVariant::query()->whereKey($variant->getKey())->lockForUpdate()->firstOrFail();

            // Only physical movements change the on-hand figure.
            if ($type->isPhysical()) {
                $newStock = $locked->stock + $quantity;
                if ($newStock < 0) {
                    throw new \RuntimeException('Остаток не может быть отрицательным.');
                }
                $locked->forceFill(['stock' => $newStock])->save();
            }

            return StockMovement::create([
                'variant_id' => $locked->id,
                'type' => $type,
                'quantity' => $quantity,
                'reason' => $reason,
                'actor_id' => $actor?->id,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
            ]);
        });
    }

    /** Units committed to open (not yet completed/cancelled) orders. */
    public function reservedFor(ProductVariant $variant): int
    {
        return (int) DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.variant_id', $variant->id)
            ->whereIn('orders.status', self::OPEN_ORDER_STATUSES)
            ->sum('order_items.quantity');
    }

    /** Available = on-hand minus units reserved by open orders. */
    public function availableFor(ProductVariant $variant): int
    {
        return $variant->stock - $this->reservedFor($variant);
    }

    /**
     * Availability for many variants at once (used by catalogue cards) without
     * an N+1 query per variant.
     *
     * @param  array<int, int>  $variantIds
     * @return array<int, int> variant_id => available
     */
    public function availableForVariants(array $variantIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $variantIds)));
        if ($ids === []) {
            return [];
        }

        $stock = ProductVariant::query()->whereIn('id', $ids)->pluck('stock', 'id');

        $reserved = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('order_items.variant_id', $ids)
            ->whereIn('orders.status', self::OPEN_ORDER_STATUSES)
            ->groupBy('order_items.variant_id')
            ->selectRaw('order_items.variant_id as variant_id, SUM(order_items.quantity) as reserved')
            ->pluck('reserved', 'variant_id');

        $available = [];
        foreach ($ids as $id) {
            $available[$id] = (int) ($stock[$id] ?? 0) - (int) ($reserved[$id] ?? 0);
        }

        return $available;
    }

    /**
     * Reserve units for a freshly placed order. Idempotent: a second call for
     * the same order is a no-op, so retries never double-reserve.
     */
    public function reservedForOrder(Order $order): void
    {
        if ($this->orderHasMovement($order, StockMovementType::Reserve)) {
            return;
        }

        foreach ($order->items as $item) {
            if ($item->variant) {
                $this->record(
                    $item->variant,
                    StockMovementType::Reserve,
                    -1 * $item->quantity,
                    'Резерв по заказу '.$order->number,
                    null,
                    $order,
                );
            }
        }
    }

    /**
     * Release a reservation (cancel/refund of a live order). The reservation is
     * status-derived, so this is audit-only and never touches on-hand stock.
     */
    public function releaseForOrder(Order $order, ?string $reason = null): void
    {
        if ($this->orderHasMovement($order, StockMovementType::Release)) {
            return;
        }

        foreach ($order->items as $item) {
            if ($item->variant) {
                $this->record(
                    $item->variant,
                    StockMovementType::Release,
                    $item->quantity,
                    $reason ?? ('Освобождение резерва по заказу '.$order->number),
                    null,
                    $order,
                );
            }
        }
    }

    /**
     * Write off on-hand stock when an order is completed. Idempotent and safe
     * against re-completion after a return.
     */
    public function writeOffForOrder(Order $order): void
    {
        if ($this->lastOrderPhysicalEffect($order) === StockMovementType::Writeoff) {
            return;
        }

        foreach ($order->items as $item) {
            if ($item->variant) {
                $this->record(
                    $item->variant,
                    StockMovementType::Writeoff,
                    -1 * $item->quantity,
                    'Списание при выполнении заказа '.$order->number,
                    null,
                    $order,
                );
            }
        }
    }

    /**
     * Return on-hand stock when a completed sale is undone (cancel, refund or
     * deletion of a completed order). Idempotent: only runs when the order's
     * last physical effect was a write-off.
     */
    public function returnForOrder(Order $order, ?string $reason = null): void
    {
        if ($this->lastOrderPhysicalEffect($order) !== StockMovementType::Writeoff) {
            return;
        }

        foreach ($order->items as $item) {
            if ($item->variant) {
                $this->record(
                    $item->variant,
                    StockMovementType::Return,
                    $item->quantity,
                    $reason ?? ('Возврат по заказу '.$order->number),
                    null,
                    $order,
                );
            }
        }
    }

    private function orderHasMovement(Order $order, StockMovementType $type): bool
    {
        return StockMovement::query()
            ->where('reference_type', $order->getMorphClass())
            ->where('reference_id', $order->getKey())
            ->where('type', $type->value)
            ->exists();
    }

    /** Latest physical movement (write-off/return) recorded for an order. */
    private function lastOrderPhysicalEffect(Order $order): ?StockMovementType
    {
        $type = StockMovement::query()
            ->where('reference_type', $order->getMorphClass())
            ->where('reference_id', $order->getKey())
            ->whereIn('type', [
                StockMovementType::Writeoff->value,
                StockMovementType::Return->value,
            ])
            ->orderByDesc('id')
            ->value('type');

        if ($type instanceof StockMovementType) {
            return $type;
        }

        return $type === null ? null : StockMovementType::from($type);
    }
}
