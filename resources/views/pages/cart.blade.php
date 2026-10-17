@extends('layouts.site')

@section('title', 'Корзина — '.setting('studio.name', 'Корни'))
@section('robots', 'noindex,follow')

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => 'Магазин', 'url' => route('shop.index')], ['label' => 'Корзина']]" />
@endsection

@section('content')
    <section class="section section--tight">
        <div class="container">
            <h1>Корзина</h1>

            @if (empty($items))
                <div class="empty" style="margin-top:24px">
                    <x-ico name="cart" />
                    <h3>Корзина пуста</h3>
                    <p>Добавьте авторские работы из магазина — здесь появятся выбранные товары.</p>
                    <a class="btn" href="{{ route('shop.index') }}">Перейти в магазин</a>
                </div>
            @else
                <div class="cart-layout" style="margin-top:24px">
                    <div class="stack">
                        @foreach ($items as $variantId => $item)
                            <div class="cart-item">
                                <img src="{{ asset($item['product']->image ?? 'images/photo-1.jpg') }}" alt="{{ $item['product']->title }}">
                                <div>
                                    <div style="font-weight:700;color:var(--ink)"><a href="{{ route('shop.show', $item['product']->slug) }}">{{ $item['product']->title }}</a></div>
                                    <div class="muted" style="font-size:14px">{{ $item['variant']->name }} · арт. {{ $item['variant']->sku }}</div>
                                    <div class="muted" style="font-size:14px">Цена: {{ money($item['variant']->price_cents) }}{{ $item['variant']->stock > 0 ? ' · в наличии '.$item['variant']->stock : ' · нет в наличии' }}</div>

                                    <div style="display:flex;gap:12px;align-items:center;margin-top:12px">
                                        <form method="post" action="{{ route('cart.update', $item['variant']->id) }}" data-qty style="display:flex;gap:12px;align-items:center">
                                            @csrf
                                            @method('PATCH')
                                            <div class="qty">
                                                <button type="button" data-qty-step="-1" aria-label="Меньше">−</button>
                                                <input type="number" name="quantity" min="0" max="20" value="{{ $item['quantity'] }}">
                                                <button type="button" data-qty-step="1" aria-label="Больше">+</button>
                                            </div>
                                            <button class="btn btn--ghost btn--sm" type="submit">Обновить</button>
                                        </form>
                                        <form method="post" action="{{ route('cart.remove', $item['variant']->id) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn--ghost btn--sm" type="submit">Удалить</button>
                                        </form>
                                    </div>
                                </div>
                                <div class="price">{{ money($item['line_total_cents']) }}</div>
                            </div>
                        @endforeach
                    </div>

                    <div class="summary">
                        <h3>Итого</h3>
                        <div class="summary__row"><span>Товары</span><span>{{ money($subtotal) }}</span></div>
                        <div class="summary__row"><span>Доставка</span><span>рассчитывается при оформлении</span></div>
                        <div class="summary__row total"><span>К оплате</span><span>{{ money($subtotal) }}</span></div>
                        <a class="btn btn--block" style="margin-top:16px" href="{{ route('checkout.index') }}">Оформить заказ</a>
                        <p class="form-note" style="margin-top:14px">Корзина не резервирует товар — остаток проверяется при оформлении.</p>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
