<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\StockService;
use Illuminate\Contracts\View\View;
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
        return view('pages.cart', [
            'items' => $this->cart->detailed(),
            'subtotal' => $this->cart->subtotalCents(),
        ]);
    }

    public function add(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $variant = ProductVariant::query()->with('product')->findOrFail($data['variant_id']);

        if (! $variant->is_active || ! $variant->product?->is_published) {
            return back()->with('error', 'Товар недоступен.');
        }

        $this->cart->add($variant->id, $data['quantity']);

        return back()->with('success', 'Товар добавлен в корзину.');
    }

    public function update(Request $request, ProductVariant $variant): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:20']]);
        $this->cart->setQuantity($variant->id, $data['quantity']);

        return back()->with('success', 'Корзина обновлена.');
    }

    public function remove(ProductVariant $variant): RedirectResponse
    {
        $this->cart->remove($variant->id);

        return back()->with('success', 'Товар удалён из корзины.');
    }
}
