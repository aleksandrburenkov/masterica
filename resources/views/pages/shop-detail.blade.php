@extends('layouts.site')

@section('title', $product->seo_title ?: ($product->title.' — купить'))
@section('meta_description', $product->seo_description ?: $product->short_description)
@section('canonical', route('shop.show', $product->slug))
@section('og_type', 'product')
@section('og_image', asset($product->image ?? 'images/photo-1.jpg'))

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => 'Магазин', 'url' => route('shop.index')], ['label' => $product->title]]" />
@endsection

@push('structured')
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->title,
            'description' => $product->short_description,
            'image' => asset($product->image ?? 'images/photo-1.jpg'),
            'offers' => [
                '@type' => 'Offer',
                'price' => (string) ($product->minPriceCents() / 100),
                'priceCurrency' => $product->currency,
                'availability' => $product->totalStock() > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'url' => route('shop.show', $product->slug),
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
@endpush

@section('content')
    <section class="section section--tight">
        <div class="container">
            <div class="detail">
                <div class="detail__media">
                    <img src="{{ asset($product->image ?? 'images/photo-1.jpg') }}" alt="{{ $product->title }}">
                </div>

                <div data-reveal>
                    <span class="chip">{{ $product->technique?->name ?? 'Авторская работа' }}</span>
                    <h1 style="margin-top:14px">{{ $product->title }}</h1>
                    <p class="lead-muted">{{ $product->short_description }}</p>

                    @if ($product->description)
                        <p>{{ $product->description }}</p>
                    @endif

                    <h3 style="margin-top:20px">Варианты и наличие</h3>
                    @if ($product->variants->isEmpty())
                        <p class="muted">Товар временно недоступен.</p>
                    @else
                        <div class="session-list">
                            @foreach ($product->variants as $variant)
                                <form class="session-item" action="{{ route('cart.add') }}" method="post">
                                    @csrf
                                    <input type="hidden" name="variant_id" value="{{ $variant->id }}">
                                    <input type="hidden" name="quantity" value="1">
                                    <div>
                                        <div class="title">{{ $variant->name }}</div>
                                        <div class="meta">
                                            <span>Артикул: {{ $variant->sku }}</span>
                                            @if ($variant->stock > 0)
                                                <span class="badge badge--green">В наличии: {{ $variant->stock }}</span>
                                            @else
                                                <span class="badge badge--gray">Нет в наличии</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div style="text-align:right;display:flex;flex-direction:column;gap:8px;align-items:flex-end">
                                        <span class="price" style="font-size:20px">{{ money($variant->price_cents) }}</span>
                                        <button class="btn btn--sm" type="submit" @disabled($variant->stock <= 0)>В корзину</button>
                                    </div>
                                </form>
                            @endforeach
                        </div>
                    @endif

                    <div class="fact-grid">
                        <div class="fact"><div class="fact__label">Получение</div><div class="fact__value">Самовывоз / доставка</div></div>
                        <div class="fact"><div class="fact__label">Срок подготовки</div><div class="fact__value">{{ setting('rules.delivery_prep_days', '3–5') }} раб. дней</div></div>
                    </div>

                    <div class="note-strip">{{ setting('rules.payment_note') }}</div>
                </div>
            </div>
        </div>
    </section>

    @if ($related->isNotEmpty())
        <section class="section" style="background:rgba(255,255,255,.5)">
            <div class="container">
                <div class="section-head"><h2>Другие работы</h2></div>
                <div class="grid grid-3">
                    @foreach ($related as $item)
                        @include('partials.product-card', ['product' => $item])
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
