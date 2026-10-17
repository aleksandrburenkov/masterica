@props(['product'])

@php
    $variant = $product->variants->firstWhere('is_active', true) ?? $product->variants->first();
    $price = $variant?->price_cents ?? $product->price_cents;
    $stock = $product->variants->sum('stock');
@endphp

<article class="card card--pad-0 catalog-card" data-reveal>
    <a class="catalog-card__media" href="{{ route('shop.show', $product->slug) }}">
        <img src="{{ asset($product->image ?? 'images/photo-1.jpg') }}" alt="{{ $product->title }}" loading="lazy">
        @if ($stock <= 0)
            <span class="catalog-card__badge" style="color:#b0323e">Нет в наличии</span>
        @endif
    </a>
    <div class="catalog-card__body">
        <h3><a href="{{ route('shop.show', $product->slug) }}">{{ $product->title }}</a></h3>
        <p class="desc">{{ $product->short_description }}</p>
        <div class="catalog-card__foot">
            <div class="price">{{ money($price) }}</div>
            @if ($stock > 0)
                <span class="badge badge--green">В наличии: {{ $stock }}</span>
            @else
                <span class="badge badge--gray">Под заказ не изготавливаем</span>
            @endif
        </div>
    </div>
</article>
