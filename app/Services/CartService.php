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

    private function put(array $cart): void
    {
        session()->put(self::KEY, $cart);
    }
}
