@extends('layouts.site')

@section('title', 'Заказ '.$order->number.' — '.setting('studio.name', 'Корни'))
@section('robots', 'noindex,nofollow')

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => 'Личный кабинет', 'url' => route('account.dashboard')], ['label' => 'Мои заказы', 'url' => route('account.orders')], ['label' => $order->number]]" />
@endsection

@section('content')
    <section class="section section--tight">
        <div class="container">
            <div class="account">
                @include('partials.account-nav')

                <div>
                    <div style="display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;align-items:center">
                        <h2 style="margin:0">Заказ {{ $order->number }}</h2>
                        <span class="badge badge--amber">{{ $order->status->label() }} · {{ $order->payment_status->label() }}</span>
                    </div>

                    <div class="fact-grid">
                        <div class="fact"><div class="fact__label">Оформлен</div><div class="fact__value">{{ optional($order->placed_at)->translatedFormat('d.m.Y H:i') }}</div></div>
                        <div class="fact"><div class="fact__label">Получение</div><div class="fact__value">{{ $order->delivery_method->label() }}</div></div>
                        <div class="fact"><div class="fact__label">Контакт</div><div class="fact__value">{{ $order->contact_name }}, {{ $order->contact_phone }}</div></div>
                        <div class="fact"><div class="fact__label">Итого</div><div class="fact__value">{{ money($order->total_cents) }}</div></div>
                    </div>

                    @if ($order->address)
                        <p class="muted">Адрес доставки: {{ $order->address }}</p>
                    @endif

                    <table class="table" style="background:#fff;border-radius:var(--radius);overflow:hidden;margin-top:12px">
                        <thead><tr><th>Товар</th><th>Артикул</th><th>Цена</th><th>Кол-во</th><th>Сумма</th></tr></thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr>
                                    <td>{{ $item->product_name }}<br><span class="muted" style="font-size:13px">{{ $item->variant_name }}</span></td>
                                    <td>{{ $item->sku }}</td>
                                    <td>{{ money($item->unit_price_cents) }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>{{ money($item->line_total_cents) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="summary" style="position:static;margin-top:18px">
                        <div class="summary__row"><span>Товары</span><span>{{ money($order->subtotal_cents) }}</span></div>
                        <div class="summary__row"><span>Доставка</span><span>{{ $order->delivery_cents > 0 ? money($order->delivery_cents) : 'бесплатно' }}</span></div>
                        <div class="summary__row total"><span>Итого</span><span>{{ money($order->total_cents) }}</span></div>
                    </div>

                    @if (auth()->user()->can('cancel', $order) || auth()->user()->can('delete', $order))
                        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:18px">
                            @if (auth()->user()->can('cancel', $order))
                                <form action="{{ route('account.order.cancel', $order->number) }}" method="post">
                                    @csrf
                                    <button class="btn btn--ghost" type="submit" onclick="return confirm('Отменить заказ?')">Отменить заказ</button>
                                </form>
                            @endif
                            @if (auth()->user()->can('delete', $order))
                                <form action="{{ route('account.order.delete', $order->number) }}" method="post">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn--ghost" type="submit" onclick="return confirm('Удалить заказ? Действие необратимо.')">Удалить заказ</button>
                                </form>
                            @endif
                        </div>
                    @endif

                    <div class="note-strip" style="margin-top:18px">Снимки названия, варианта, артикула, цены и валюты сохранены на момент оформления и не перезаписываются.</div>
                </div>
            </div>
        </div>
    </section>
@endsection
