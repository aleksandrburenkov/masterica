<?php

namespace App\Http\Controllers;

use App\Enums\DeliveryMethod;
use App\Models\Order;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly OrderService $orders,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $items = $this->cart->detailed();
        if ($items === []) {
            return redirect()->route('cart.index')->with('info', 'Корзина пуста.');
        }

        return view('pages.checkout', [
            'items' => $items,
            'subtotal' => $this->cart->subtotalCents(),
            'deliveryOptions' => $this->deliveryOptions(),
            'prepDays' => setting('rules.delivery_prep_days', '3–5'),
            'paymentNote' => setting('rules.payment_note'),
        ]);
    }

    public function store(Request $request): RedirectResponse|View|JsonResponse
    {
        $data = $request->validate([
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_phone' => ['required', 'string', 'max:32'],
            'contact_email' => ['required', 'email', 'max:160'],
            'delivery_method' => ['required', 'in:pickup,city_delivery'],
            'address' => ['nullable', 'required_if:delivery_method,city_delivery', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:1000'],
            // Акцепт Публичной оферты (договора купли-продажи) — обязателен.
            'accept_offer' => ['accepted'],
            'consent_data' => ['accepted'],
        ]);

        $order = $this->orders->place($request->user(), $data);

        // Ошибки валидации при AJAX-отправке возвращаются как JSON и
        // показываются уведомлением без перезагрузки страницы.
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Заказ '.$order->number.' оформлен.',
                'redirect' => route('checkout.success', $order->number),
            ], 201);
        }

        return redirect()->route('checkout.success', $order->number);
    }

    public function success(Request $request, Order $order): View
    {
        $this->authorize('view', $order);

        $order->load('items');

        return view('pages.checkout-success', compact('order'));
    }

    /** @return array<int, array{value:string,label:string,price_cents:int}> */
    private function deliveryOptions(): array
    {
        return collect(DeliveryMethod::cases())->map(fn (DeliveryMethod $method) => [
            'value' => $method->value,
            'label' => $method->label(),
            'price_cents' => $this->orders->deliveryCents($method),
        ])->all();
    }
}
