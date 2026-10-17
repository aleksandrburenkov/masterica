@props(['course'])

<article class="card card--pad-0 catalog-card" data-reveal>
    <a class="catalog-card__media" href="{{ route('courses.show', $course->slug) }}">
        <img src="{{ asset($course->image ?? 'images/hero-masterclass.png') }}" alt="{{ $course->title }}" loading="lazy">
        <span class="catalog-card__badge">Онлайн-курс</span>
    </a>
    <div class="catalog-card__body">
        <h3><a href="{{ route('courses.show', $course->slug) }}">{{ $course->title }}</a></h3>
        <p class="desc">{{ $course->short_description }}</p>
        <div class="catalog-card__meta">
            <span>{{ $course->modules->count() ?: $course->modules_count }} модулей</span>
            <span>доступ {{ (int) round($course->access_days / 30) }} мес.</span>
        </div>
        <div class="catalog-card__foot">
            <div class="price">{{ money($course->price_cents) }}</div>
            <a class="card-link" href="{{ route('courses.show', $course->slug) }}">Открыть курс</a>
        </div>
    </div>
</article>
