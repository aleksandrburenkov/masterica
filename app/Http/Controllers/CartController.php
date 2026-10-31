<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\StockService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly StockService $stock,
    ) {}

    public function index(): View
    {
        $items = $this->cart->detailed();

        if ($items !== []) {
            $available = $this->stock->availableForVariants(array_keys($items));
            foreach ($items as $variantId => $item) {
                $item['variant']->setAttribute(
                    'available_stock',
                    max(0, $available[$variantId] ?? (int) $item['variant']->stock),
                );
            }
        }

        return view('pages.cart', [
            'items' => $items,
            'subtotal' => $this->cart->subtotalCents(),
        ]);
    }

    /**
     * Return the current cart as JSON so the header mini-cart can stay in sync
     * with the server after every add / update / remove action.
     */
    public function state(): JsonResponse
    {
        return response()->json(['cart' => $this->cart->state()]);
    }

    public function add(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $variant = ProductVariant::query()->with('product')->findOrFail($data['variant_id']);

        if (! $variant->is_active || ! $variant->product?->is_published) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Товар недоступен.'], 422);
            }

            return back()->with('error', 'Товар недоступен.');
        }

        // Корзина не может содержать больше единиц, чем есть в наличии.
        $current = (int) ($this->cart->raw()[$variant->id] ?? 0);
        $target = min($current + (int) $data['quantity'], $this->stockLimit($variant));
        $this->cart->setQuantity($variant->id, $target);

        $message = 'Товар добавлен в корзину.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'cart' => $this->cart->state()], 201);
        }

        return back()->with('success', $message);
    }

    public function update(Request $request, ProductVariant $variant): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:20']]);
        $quantity = (int) $data['quantity'];

        // Ноль удаляет позицию, любое положительное значение ограничено остатком.
        if ($quantity > 0) {
            $quantity = min($quantity, $this->stockLimit($variant));
        }

        $this->cart->setQuantity($variant->id, $quantity);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Корзина обновлена.', 'cart' => $this->cart->state()]);
        }

        return back()->with('success', 'Корзина обновлена.');
    }

    public function remove(Request $request, ProductVariant $variant): RedirectResponse|JsonResponse
    {
        $this->cart->remove($variant->id);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Товар удалён из корзины.', 'cart' => $this->cart->state()]);
        }

        return back()->with('success', 'Товар удалён из корзины.');
    }

    /** Максимально допустимое количество в корзине: доступный остаток (без резервов), но не меньше 1 и не больше 20. */
    private function stockLimit(ProductVariant $variant): int
    {
        return max(1, min(20, $this->stock->availableFor($variant)));
    }
}
