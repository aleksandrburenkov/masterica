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
            if (in_array($type, [StockMovementType::Receipt, StockMovementType::Writeoff, StockMovementType::Adjustment], true)) {
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

    public function reservedForOrder(Order $order): void
    {
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

    public function releaseForOrder(Order $order, ?string $reason = null): void
    {
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

    public function writeOffForOrder(Order $order): void
    {
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
}
