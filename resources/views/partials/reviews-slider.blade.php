@props(['reviews'])

@push('head')
    <link rel="stylesheet" href="{{ asset('vendor/swiper/swiper-bundle.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/site/modules/reviews-slider.css') }}">
@endpush

<div class="reviews-slider" data-reviews-slider>
    <div class="swiper" role="group" aria-roledescription="carousel" aria-label="Отзывы клиентов">
        <div class="swiper-wrapper">
            @foreach ($reviews as $review)
                <div class="swiper-slide">
                    @include('partials.review-card', ['review' => $review])
                </div>
            @endforeach
        </div>
    </div>

    <div class="reviews-slider__nav">
        <button type="button" class="reviews-slider__arrow reviews-slider__arrow--prev" data-reviews-prev aria-label="Предыдущий отзыв">
            <x-ico name="chevron-right" />
        </button>
        <button type="button" class="reviews-slider__arrow reviews-slider__arrow--next" data-reviews-next aria-label="Следующий отзыв">
            <x-ico name="chevron-right" />
        </button>
    </div>
</div>

@push('scripts')
    <script src="{{ asset('vendor/swiper/swiper-bundle.min.js') }}"></script>
    <script src="{{ asset('js/site/modules/reviews-slider.js') }}" defer></script>
@endpush
