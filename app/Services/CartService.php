<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;

/**
 * Session-backed shopping cart. The cart never reserves stock by itself;
 * stock is validated and reserved server-side when an order is placed.
 */
class CartService
{
    private const KEY = 'cart';

    public function __construct(
        private readonly StockService $stock,
    ) {}

    /** @return array<int, int> variant_id => quantity */
    public function raw(): array
    {
        $cart = session()->get(self::KEY, []);

        return is_array($cart) ? $cart : [];
    }

    public function add(int $variantId, int $quantity = 1): void
    {
        $quantity = max(1, $quantity);
        $cart = $this->raw();
        $cart[$variantId] = ($cart[$variantId] ?? 0) + $quantity;
        $this->put($cart);
    }

    public function setQuantity(int $variantId, int $quantity): void
    {
        $cart = $this->raw();
        if ($quantity <= 0) {
            unset($cart[$variantId]);
        } else {
            $cart[$variantId] = $quantity;
        }
        $this->put($cart);
    }

    public function remove(int $variantId): void
    {
        $cart = $this->raw();
        unset($cart[$variantId]);
        $this->put($cart);
    }

    public function clear(): void
    {
        session()->forget(self::KEY);
    }

    public function count(): int
    {
        return array_sum($this->raw());
    }

    /**
     * Resolve the cart against the catalogue, dropping items whose product
     * or variant is no longer available.
     *
     * @return array<int, array{variant:ProductVariant,product:Product,quantity:int,line_total_cents:int}>
     */
    public function detailed(): array
    {
        $cart = $this->raw();
        if ($cart === []) {
            return [];
        }

        $variants = ProductVariant::query()
            ->with('product')
            ->whereIn('id', array_keys($cart))
            ->get()
            ->keyBy('id');

        $items = [];
        foreach ($cart as $variantId => $quantity) {
            $variant = $variants->get($variantId);
            if (! $variant || ! $variant->is_active || ! $variant->product?->is_published) {
                continue;
            }
            $quantity = max(1, (int) $quantity);
            $items[$variantId] = [
                'variant' => $variant,
                'product' => $variant->product,
                'quantity' => $quantity,
                'line_total_cents' => $variant->price_cents * $quantity,
            ];
        }

        return $items;
    }

    public function subtotalCents(): int
    {
        $total = 0;
        foreach ($this->detailed() as $item) {
            $total += $item['line_total_cents'];
        }

        return $total;
    }

    /**
     * Serialize the resolved cart for the storefront (header mini-cart and JSON
     * responses). Only items that survive catalogue validation are included, so
     * the badge count always matches what the shopper can actually see.
     *
     * @return array{count:int,subtotal_cents:int,subtotal:string,items:array<int,array<string,mixed>>}
     */
    public function state(): array
    {
        $detailed = $this->detailed();
        $available = $this->stock->availableForVariants(array_keys($detailed));

        $items = [];

        foreach ($detailed as $variantId => $item) {
            /** @var ProductVariant $variant */
            $variant = $item['variant'];
            $product = $item['product'];
            $quantity = (int) $item['quantity'];
            $variantAvailable = max(0, $available[$variantId] ?? (int) $variant->stock);

            $items[] = [
                'variant_id' => (int) $variantId,
                'product_id' => (int) $product->getKey(),
                'title' => $product->title,
                'url' => route('shop.show', $product->slug),
                'variant_name' => $variant->name,
                'sku' => $variant->sku,
                'quantity' => $quantity,
                'price_cents' => (int) $variant->price_cents,
                'price' => money((int) $variant->price_cents),
                'line_total_cents' => (int) $item['line_total_cents'],
                'line_total' => money((int) $item['line_total_cents']),
                'image' => media_url($product->image, 'images/photo-1.jpg'),
                // Наличие с учётом резервов по оформленным заказам.
                'stock' => $variantAvailable,
                'max' => max(1, min(20, $variantAvailable)),
            ];
        }

        $subtotal = 0;
        $count = 0;
        foreach ($items as $item) {
            $subtotal += $item['line_total_cents'];
            $count += $item['quantity'];
        }

        return [
            'count' => $count,
            'subtotal_cents' => $subtotal,
            'subtotal' => money($subtotal),
            'items' => $items,
        ];
    }

    private function put(array $cart): void
    {
        session()->put(self::KEY, $cart);
    }
}
