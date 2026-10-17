@props(['masterclass'])

<article class="card card--pad-0 catalog-card" data-reveal>
    <a class="catalog-card__media" href="{{ route('masterclasses.show', $masterclass->slug) }}">
        <img src="{{ asset($masterclass->image ?? 'images/hero-masterclass.png') }}" alt="{{ $masterclass->title }}" loading="lazy">
        @if ($masterclass->technique)
            <span class="catalog-card__badge">{{ $masterclass->technique->name }}</span>
        @endif
    </a>
    <div class="catalog-card__body">
        <h3><a href="{{ route('masterclasses.show', $masterclass->slug) }}">{{ $masterclass->title }}</a></h3>
        <p class="desc">{{ $masterclass->short_description }}</p>
        <div class="catalog-card__meta">
            <span><x-ico name="users" style="width:15px;height:15px;vertical-align:-2px" /> {{ $masterclass->ageLabel() }}</span>
            <span><x-ico name="clock" style="width:15px;height:15px;vertical-align:-2px" /> {{ $masterclass->durationLabel() }}</span>
        </div>
        <div class="catalog-card__foot">
            <div class="price">{{ money($masterclass->price_cents) }} <small>/ участник</small></div>
            <a class="card-link" href="{{ route('masterclasses.show', $masterclass->slug) }}">Подробнее</a>
        </div>
    </div>
</article>
