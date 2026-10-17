@extends('layouts.site')

@section('title', 'Магазин авторских работ — '.setting('studio.name', 'Корни'))
@section('meta_description', 'Авторские изделия из бумажной лозы и макраме. Цена, вариант и наличие указаны в карточке товара.')
@section('canonical', route('shop.index'))

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => 'Магазин']]" />
@endsection

@section('content')
    <section class="hero">
        <div class="container">
            <div class="hero__inner" style="min-height:auto">
                <div class="hero__content" data-reveal>
                    <h1>Магазин авторских работ</h1>
                    <p class="lead">Изделия ручной работы из бумажной лозы и макраме. Готовые работы — срок подготовки {{ setting('rules.delivery_prep_days', '3–5') }} рабочих дней.</p>
                </div>
                <div class="hero__media" style="height:auto"><img src="{{ asset('images/photo-3.jpg') }}" alt="Авторские работы студии" loading="lazy"></div>
            </div>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container">
            <div class="filters">
                <a class="filter-pill {{ !request('technique') ? 'is-active' : '' }}" href="{{ route('shop.index') }}">Все работы</a>
                @foreach ($techniques as $technique)
                    <a class="filter-pill {{ request('technique') === $technique->slug ? 'is-active' : '' }}" href="{{ route('shop.index', ['technique' => $technique->slug]) }}">{{ $technique->name }}</a>
                @endforeach
            </div>

            @if ($products->isEmpty())
                <div class="empty">
                    <x-ico name="box" />
                    <h3>В этом разделе пока нет товаров</h3>
                    <p>Загляните позже или посмотрите другие разделы магазина.</p>
                    <a class="btn btn--ghost" href="{{ route('shop.index') }}">Все работы</a>
                </div>
            @else
                <div class="grid grid-3">
                    @foreach ($products as $product)
                        @include('partials.product-card', ['product' => $product])
                    @endforeach
                </div>
                <div style="margin-top:36px">{{ $products->links() }}</div>
            @endif

            <div class="note-strip" style="margin-top:32px">
                Оплата при получении. Самовывоз из студии — бесплатно, доставка по {{ setting('studio.city', 'Брянску') }} — {{ money((int) setting('pricing.delivery_city_cents', 40000)) }}.
            </div>
        </div>
    </section>
@endsection
