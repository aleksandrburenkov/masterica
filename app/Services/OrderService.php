<?php

namespace App\Services;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\NumberGenerator;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private readonly CartService $cart,
        private readonly StockService $stock,
        private readonly AuditLogger $audit,
        private readonly NotificationService $notifications,
    ) {}

    public function deliveryCents(DeliveryMethod $method): int
    {
        return match ($method) {
            DeliveryMethod::Pickup => Settings::int('pricing.delivery_pickup_cents', 0),
            DeliveryMethod::CityDelivery => Settings::int('pricing.delivery_city_cents', 40000),
        };
    }

    /**
     * Place an order from the session cart. Prices, availability and totals
     * are revalidated against the database inside a locking transaction.
     */
    public function place(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            $raw = $this->cart->raw();

            if ($raw === []) {
                throw ValidationException::withMessages(['cart' => 'Корзина пуста.']);
            }

            $variantIds = array_map('intval', array_keys($raw));

            // Lock variant rows first, before any consistent read, so
            // concurrent checkouts are serialized and the availability
            // snapshot below includes other committed reservations.
            $variants = ProductVariant::query()
                ->whereIn('id', $variantIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $products = Product::query()
                ->whereIn('id', $variants->pluck('product_id')->unique()->all())
                ->get()
                ->keyBy('id');

            $currency = setting('rules.currency', 'RUB');
            $subtotal = 0;
            $lines = [];

            foreach ($raw as $variantId => $rawQuantity) {
                /** @var ProductVariant|null $variant */
                $variant = $variants->get((int) $variantId);
                $quantity = max(1, (int) $rawQuantity);

                if (! $variant) {
                    throw ValidationException::withMessages(['cart' => 'Товар больше недоступен.']);
                }

                $product = $products->get($variant->product_id);

                if (! $variant->is_active || ! $product?->is_published) {
                    throw ValidationException::withMessages([
                        'cart' => 'Товар «'.($product?->title ?? $variant->name).'» больше недоступен.',
                    ]);
                }

                $available = $this->stock->availableFor($variant);
                if ($quantity > $available) {
                    throw ValidationException::withMessages([
                        'cart' => 'Недостаточно остатка для «'.$product->title.'» ('.$variant->name.').',
                    ]);
                }

                $lineTotal = $variant->price_cents * $quantity;
                $subtotal += $lineTotal;

                $lines[] = [
                    'product_id' => $variant->product_id,
                    'variant_id' => $variant->id,
                    'product_name' => $product->title,
                    'variant_name' => $variant->name,
                    'sku' => $variant->sku,
                    'unit_price_cents' => $variant->price_cents,
                    'quantity' => $quantity,
                    'line_total_cents' => $lineTotal,
                    'currency' => $currency,
                ];
            }

            $method = DeliveryMethod::from($data['delivery_method']);
            $delivery = $this->deliveryCents($method);

            $order = Order::create([
                'number' => NumberGenerator::order(),
                'user_id' => $user->id,
                'status' => OrderStatus::PendingPayment,
                'payment_status' => PaymentStatus::Unpaid,
                'delivery_method' => $method,
                'subtotal_cents' => $subtotal,
                'delivery_cents' => $delivery,
                'total_cents' => $subtotal + $delivery,
                'currency' => $currency,
                'contact_name' => $data['contact_name'],
                'contact_phone' => $data['contact_phone'],
                'contact_email' => $data['contact_email'],
                'address' => $method === DeliveryMethod::CityDelivery ? ($data['address'] ?? null) : null,
                'comment' => $data['comment'] ?? null,
                'consent_marketing' => (bool) ($data['consent_marketing'] ?? false),
                'placed_at' => now(),
            ]);

            foreach ($lines as $line) {
                $order->items()->create($line);
            }

            $order->load('items.variant');
            $this->stock->reservedForOrder($order);
            $this->cart->clear();

            $this->audit->log($user, 'order.placed', $order, null, [
                'total_cents' => $order->total_cents,
            ]);
            $this->notifications->notify(
                $user,
                'Заказ оформлен',
                'Заказ '.$order->number.' принят. Оплата при получении.',
            );

            return $order->refresh();
        });
    }

    public function markPaid(Order $order, ?User $actor = null): Order
    {
        if ($order->payment_status === PaymentStatus::Paid
            || in_array($order->status, [OrderStatus::Completed, OrderStatus::Cancelled, OrderStatus::Refunded], true)) {
            return $order;
        }

        $order->forceFill([
            'payment_status' => PaymentStatus::Paid,
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
        ])->save();

        $this->audit->log($actor, 'order.paid', $order, 'Тестовая отметка оплаты');
        $this->notifications->notify($order->user, 'Заказ оплачен', 'Оплата по заказу '.$order->number.' отмечена.');

        return $order;
    }

    public function complete(Order $order, ?User $actor = null): Order
    {
        if (in_array($order->status, [OrderStatus::Completed, OrderStatus::Cancelled, OrderStatus::Refunded], true)) {
            return $order;
        }

        DB::transaction(function () use ($order, $actor) {
            $order->load('items.variant');
            $this->stock->writeOffForOrder($order);

            $order->forceFill([
                'status' => OrderStatus::Completed,
                'completed_at' => now(),
            ])->save();

            $this->audit->log($actor, 'order.completed', $order);
            $this->notifications->notify($order->user, 'Заказ выполнен', 'Заказ '.$order->number.' завершён.');
        });

        return $order->refresh();
    }

    public function cancel(Order $order, ?string $reason = null, ?User $actor = null): Order
    {
        if (in_array($order->status, [OrderStatus::Completed, OrderStatus::Cancelled], true)) {
            return $order;
        }

        DB::transaction(function () use ($order, $reason, $actor) {
            $order->load('items.variant');
            $this->stock->releaseForOrder($order, $reason);

            $order->forceFill([
                'status' => OrderStatus::Cancelled,
            ])->save();

            $this->audit->log($actor ?? $order->user, 'order.cancelled', $order, $reason);
        });

        return $order->refresh();
    }
}
