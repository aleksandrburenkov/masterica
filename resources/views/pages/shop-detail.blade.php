@extends('layouts.site')

@php
    $gallery = collect($product->gallery ?? [])->filter()->take(6)->values();
    $galleryCount = $gallery->count();
@endphp

@section('title', $product->seo_title ?: ($product->title.' — купить'))
@section('meta_description', $product->seo_description ?: $product->short_description)
@section('canonical', route('shop.show', $product->slug))
@section('og_type', 'product')
@section('og_image', media_url($product->image, 'images/photo-1.jpg'))

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => 'Магазин', 'url' => route('shop.index')], ['label' => $product->title]]" />
@endsection

@if ($gallery->isNotEmpty())
    @push('head')
        <link rel="stylesheet" href="{{ asset('vendor/swiper/swiper-bundle.min.css') }}">
        <link rel="stylesheet" href="{{ asset('css/site/modules/product-gallery.css') }}">
    @endpush

    @push('scripts')
        <script src="{{ asset('vendor/swiper/swiper-bundle.min.js') }}"></script>
        <script src="{{ asset('js/site/modules/product-gallery.js') }}" defer></script>
    @endpush
@endif

@push('structured')
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->title,
            'description' => $product->short_description,
            'image' => media_url($product->image, 'images/photo-1.jpg'),
            'offers' => [
                '@type' => 'Offer',
                'price' => (string) ($product->minPriceCents() / 100),
                'priceCurrency' => $product->currency,
                'availability' => ($product->available_stock ?? max(0, $product->totalStock())) > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
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
                    <img src="{{ media_url($product->image, 'images/photo-1.jpg') }}" alt="{{ $product->title }}" data-product-gallery-main>

                    @if ($gallery->isNotEmpty())
                        <div class="product-gallery product-gallery--{{ $galleryCount > 4 ? 'fill' : 'compact' }} product-gallery--count-{{ $galleryCount }}"
                            data-product-gallery>
                            <div class="swiper product-gallery__swiper">
                                <div class="swiper-wrapper">
                                    @foreach ($gallery as $index => $image)
                                        <div class="swiper-slide">
                                            <button type="button" class="product-gallery__thumb"
                                                data-gallery-src="{{ media_url($image) }}"
                                                data-gallery-alt="{{ $product->title }} — фото {{ $index + 1 }}"
                                                aria-label="Показать фото {{ $index + 1 }}">
                                                <img src="{{ media_url($image) }}" alt="" loading="lazy">
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div data-reveal>
                    <span class="chip">{{ $product->technique?->name ?? 'Авторская работа' }}</span>
                    <h1 style="margin-top:14px">{{ $product->title }}</h1>
                    <p class="lead-muted">{{ $product->short_description }}</p>

                    @if ($product->sizes)
                        <p class="muted">Размеры: {{ $product->sizes }}</p>
                    @endif

                    @if ($product->description)
                        <p>{{ $product->description }}</p>
                    @endif

                    <h3 style="margin-top:20px">Варианты и наличие</h3>
                    @if ($product->variants->isEmpty())
                        <p class="muted">Товар временно недоступен.</p>
                    @else
                        <div class="session-list">
                            @foreach ($product->variants as $variant)
                                @php($available = $variant->available_stock ?? max(0, $variant->stock))
                                <form class="session-item" action="{{ route('cart.add') }}" method="post" data-ajax-form>
                                    @csrf
                                    <input type="hidden" name="variant_id" value="{{ $variant->id }}">
                                    <input type="hidden" name="quantity" value="1">
                                    <div>
                                        <div class="title">{{ $variant->name }}</div>
                                        <div class="meta">
                                            <span>Артикул: {{ $variant->sku }}</span>
                                            @if ($available > 0)
                                                <span class="badge badge--green">В наличии: {{ $available }}</span>
                                            @else
                                                <span class="badge badge--gray">Нет в наличии</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div style="text-align:right;display:flex;flex-direction:column;gap:8px;align-items:flex-end">
                                        <span class="price" style="font-size:20px">
                                            @if ($product->hasSalePrice())
                                                <s class="strike price__old">{{ money($product->price_cents) }}</s>
                                                <span class="price__sale">{{ money($variant->price_cents) }}</span>
                                            @else
                                                {{ money($variant->price_cents) }}
                                            @endif
                                        </span>
                                        <button class="btn btn--sm" type="submit" @disabled($available <= 0)>В корзину</button>
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
