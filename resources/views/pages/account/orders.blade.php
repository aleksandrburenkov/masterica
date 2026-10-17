@extends('layouts.site')

@section('title', 'Мои заказы — '.setting('studio.name', 'Корни'))
@section('robots', 'noindex,nofollow')

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => 'Личный кабинет', 'url' => route('account.dashboard')], ['label' => 'Мои заказы']]" />
@endsection

@section('content')
    <section class="section section--tight">
        <div class="container">
            <div class="account">
                @include('partials.account-nav')

                <div>
                    <h2>Мои заказы</h2>
                    <p class="muted">История покупок и статусы получения.</p>

                    @if ($orders->isEmpty())
                        <div class="empty">
                            <x-ico name="box" />
                            <h3>Заказов пока нет</h3>
                            <p>Выберите авторские работы в магазине.</p>
                            <a class="btn" href="{{ route('shop.index') }}">Перейти в магазин</a>
                        </div>
                    @else
                        <table class="table" style="background:#fff;border-radius:var(--radius);overflow:hidden">
                            <thead><tr><th>Заказ</th><th>Дата</th><th>Состав</th><th>Сумма</th><th>Статус</th><th></th></tr></thead>
                            <tbody>
                                @foreach ($orders as $order)
                                    <tr>
                                        <td><strong>{{ $order->number }}</strong></td>
                                        <td>{{ optional($order->placed_at)->translatedFormat('d.m.Y') }}</td>
                                        <td>{{ $order->items->count() }} поз.</td>
                                        <td>{{ money($order->total_cents) }}</td>
                                        <td><span class="badge {{ $order->status->value === 'completed' ? 'badge--green' : 'badge--amber' }}">{{ $order->status->label() }}</span></td>
                                        <td><a class="card-link" href="{{ route('account.order', $order->number) }}">Открыть</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
