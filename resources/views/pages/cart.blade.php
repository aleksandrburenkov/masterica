@extends('layouts.site')

@section('title', 'Корзина — '.setting('studio.name', 'Корни'))
@section('robots', 'noindex,follow')

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => 'Магазин', 'url' => route('shop.index')], ['label' => 'Корзина']]" />
@endsection

@section('content')
    <section class="section section--tight">
        <div class="container" data-cart-page
            data-cart-url="{{ route('cart.state') }}"
            data-cart-update-url="{{ route('cart.update', '__VARIANT__') }}"
            data-cart-remove-url="{{ route('cart.remove', '__VARIANT__') }}"
            data-cart-shop-url="{{ route('shop.index') }}">
            <h1>Корзина</h1>

            @if (empty($items))
                <div class="empty" style="margin-top:24px">
                    <x-ico name="cart" />
                    <h3>Корзина пуста</h3>
                    <p>Добавьте авторские работы из магазина — здесь появятся выбранные товары.</p>
                    <a class="btn" href="{{ route('shop.index') }}">Перейти в магазин</a>
                </div>
            @else
                <div class="cart-layout" style="margin-top:24px" data-cart-layout>
                    <div class="stack">
                        @foreach ($items as $variantId => $item)
                            @php
                                $variant = $item['variant'];
                                $available = (int) ($variant->available_stock ?? $variant->stock);
                                $max = max(1, min(20, $available));
                            @endphp
                            <div class="cart-item" data-cart-item data-variant-id="{{ $variant->id }}"
                                data-cart-max="{{ $max }}">
                                <img src="{{ media_url($item['product']->image, 'images/photo-1.jpg') }}"
                                    alt="{{ $item['product']->title }}">
                                <div>
                                    <div style="font-weight:700;color:var(--ink)"><a href="{{ route('shop.show', $item['product']->slug) }}">{{ $item['product']->title }}</a></div>
                                    <div class="muted" style="font-size:14px">{{ $variant->name }} · арт. {{ $variant->sku }}</div>
                                    <div class="muted" style="font-size:14px">Цена: {{ money($variant->price_cents) }}{{ $available > 0 ? ' · в наличии '.$available : ' · нет в наличии' }}</div>

                                    <div style="display:flex;gap:12px;align-items:center;margin-top:12px">
                                        <form method="post" action="{{ route('cart.update', $variant->id) }}" data-cart-update-form style="display:flex;gap:12px;align-items:center">
                                            @csrf
                                            @method('PATCH')
                                            <div class="qty">
                                                <button type="button" data-cart-step="-1" aria-label="Уменьшить количество" @disabled($item['quantity'] <= 1)>−</button>
                                                <input type="number" name="quantity" min="1" max="{{ $max }}" value="{{ $item['quantity'] }}"
                                                    data-cart-qty aria-label="Количество товара">
                                                <button type="button" data-cart-step="1" aria-label="Увеличить количество" @disabled($item['quantity'] >= $max)>+</button>
                                            </div>
                                            <button class="btn btn--ghost btn--sm" type="submit" data-cart-submit>Обновить</button>
                                        </form>
                                        <form method="post" action="{{ route('cart.remove', $variant->id) }}" data-cart-remove-form>
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn--ghost btn--sm" type="submit" data-cart-remove>Удалить</button>
                                        </form>
                                    </div>
                                </div>
                                <div class="price" data-cart-line-total>{{ money($item['line_total_cents']) }}</div>
                            </div>
                        @endforeach
                    </div>

                    <div class="summary" data-cart-summary>
                        <h3>Итого</h3>
                        <div class="summary__row"><span>Товары</span><span data-cart-subtotal>{{ money($subtotal) }}</span></div>
                        <div class="summary__row"><span>Доставка</span><span>рассчитывается при оформлении</span></div>
                        <div class="summary__row total"><span>К оплате</span><span data-cart-total>{{ money($subtotal) }}</span></div>
                        <a class="btn btn--block" style="margin-top:16px" href="{{ route('checkout.index') }}">Оформить заказ</a>
                        <p class="form-note" style="margin-top:14px">Корзина не резервирует товар — остаток проверяется при оформлении.</p>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
