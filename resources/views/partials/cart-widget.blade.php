@php
    $cart = app(\App\Services\CartService::class);
    $cartState = $cart->state();
    $cartCount = $cartState['count'];
@endphp

<div class="cart-widget" data-cart
    data-cart-url="{{ route('cart.state') }}"
    data-cart-update-url="{{ route('cart.update', '__VARIANT__') }}"
    data-cart-remove-url="{{ route('cart.remove', '__VARIANT__') }}">
    <button type="button" class="icon-btn cart-widget__toggle" data-cart-toggle
        aria-expanded="false" aria-controls="header-cart-panel" aria-haspopup="dialog"
        aria-label="Корзина, товаров: {{ $cartCount }}">
        <x-ico name="cart" />
        <span class="cart-widget__count" data-cart-count{{ $cartCount === 0 ? ' hidden' : '' }}>{{ $cartCount }}</span>
    </button>

    <div class="cart-panel" id="header-cart-panel" data-cart-panel role="dialog" aria-label="Корзина">
        <div class="cart-panel__head">
            <strong>Корзина</strong>
            <button type="button" class="cart-panel__close" data-cart-close aria-label="Закрыть корзину">
                <x-ico name="close" />
            </button>
        </div>

        <div class="cart-panel__body" data-cart-body>
            @if ($cartCount === 0)
                <div class="cart-panel__empty">
                    <x-ico name="cart" />
                    <p>Корзина пуста.<br>Добавьте работы из магазина.</p>
                </div>
            @else
                @foreach ($cartState['items'] as $item)
                    <div class="cart-line" data-cart-variant="{{ $item['variant_id'] }}" data-cart-max="{{ $item['max'] }}">
                        <a class="cart-line__media" href="{{ $item['url'] }}">
                            <img src="{{ $item['image'] }}" alt="" loading="lazy">
                        </a>
                        <div class="cart-line__info">
                            <a class="cart-line__title" href="{{ $item['url'] }}">{{ $item['title'] }}</a>
                            <div class="cart-line__meta">{{ $item['variant_name'] }} · арт. {{ $item['sku'] }}</div>
                            <div class="cart-line__controls">
                                <div class="qty qty--sm">
                                    <button type="button" data-cart-step="-1" aria-label="Уменьшить количество" @disabled($item['quantity'] <= 1)>−</button>
                                    <input type="number" min="1" max="{{ $item['max'] }}" value="{{ $item['quantity'] }}"
                                        data-cart-qty aria-label="Количество товара">
                                    <button type="button" data-cart-step="1" aria-label="Увеличить количество" @disabled($item['quantity'] >= $item['max'])>+</button>
                                </div>
                                <button type="button" class="cart-line__remove" data-cart-remove
                                    aria-label="Удалить «{{ $item['title'] }}» из корзины">
                                    <x-ico name="trash" />
                                </button>
                            </div>
                        </div>
                        <div class="cart-line__price">{{ $item['line_total'] }}</div>
                    </div>
                @endforeach
            @endif
        </div>

        <div class="cart-panel__foot" data-cart-foot{{ $cartCount === 0 ? ' hidden' : '' }}>
            <div class="cart-panel__total">
                <span>Итого</span>
                <span data-cart-subtotal>{{ $cartState['subtotal'] }}</span>
            </div>
            <div class="cart-panel__actions">
                <a class="btn btn--ghost btn--sm" href="{{ route('cart.index') }}">Корзина</a>
                <a class="btn btn--sm" href="{{ route('checkout.index') }}">Оформить</a>
            </div>
        </div>
    </div>
</div>
