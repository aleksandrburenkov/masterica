@extends('layouts.site')

@section('title', 'Заказ '.$order->number.' принят — '.setting('studio.name', 'Корни'))
@section('robots', 'noindex,nofollow')

@section('content')
    <section class="section">
        <div class="container" style="max-width:900px">
            <div class="card" data-reveal>
                <span class="badge badge--green"><x-ico name="check" style="width:14px;height:14px" /> Заказ оформлен</span>
                <h1 style="margin-top:14px">Заказ {{ $order->number }} принят</h1>
                <p class="lead-muted">Мы свяжемся по указанным контактам для подтверждения. Оплата при получении.</p>

                <div class="fact-grid">
                    <div class="fact"><div class="fact__label">Статус заказа</div><div class="fact__value">{{ $order->status->label() }}</div></div>
                    <div class="fact"><div class="fact__label">Статус оплаты</div><div class="fact__value">{{ $order->payment_status->label() }}</div></div>
                    <div class="fact"><div class="fact__label">Получение</div><div class="fact__value">{{ $order->delivery_method->label() }}</div></div>
                    <div class="fact"><div class="fact__label">Сумма</div><div class="fact__value">{{ money($order->total_cents) }}</div></div>
                </div>

                <h3 style="margin-top:20px">Состав заказа</h3>
                <table class="table">
                    <thead><tr><th>Товар</th><th>Артикул</th><th>Кол-во</th><th>Сумма</th></tr></thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            <tr>
                                <td>{{ $item->product_name }}<br><span class="muted" style="font-size:13px">{{ $item->variant_name }}</span></td>
                                <td>{{ $item->sku }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ money($item->line_total_cents) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="summary__row"><span>Доставка</span><span>{{ $order->delivery_cents > 0 ? money($order->delivery_cents) : 'бесплатно' }}</span></div>
                <div class="summary__row total"><span>Итого</span><span>{{ money($order->total_cents) }}</span></div>

                <div style="display:flex;gap:12px;margin-top:20px;flex-wrap:wrap">
                    <a class="btn" href="{{ route('account.orders') }}">Мои заказы</a>
                    <a class="btn btn--ghost" href="{{ route('shop.index') }}">Продолжить покупки</a>
                </div>
            </div>
        </div>
    </section>
@endsection
