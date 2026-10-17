@props(['review'])

<article class="card review-card" data-reveal>
    <div class="review-card__head">
        <span class="avatar">{{ mb_substr($review->user?->name ?? 'Гость', 0, 1) }}</span>
        <div>
            <div style="font-weight:700;color:var(--ink)">{{ $review->user?->name ?? 'Пользователь' }}</div>
            <div class="meta-row" style="font-size:13px">
                @if ($review->city)<span>{{ $review->city }}</span>@endif
                @if ($review->masterclass)<span>{{ $review->masterclass->title }}</span>@endif
            </div>
        </div>
        @if ($review->is_demo)
            <span class="badge badge--gray demo-tag" style="margin-left:auto">Демо-отзыв</span>
        @endif
    </div>

    @if ($review->rating)
        <div class="review-card__stars" aria-label="Оценка {{ $review->rating }} из 5">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</div>
    @endif

    @if ($review->type->value === 'video')
        <div class="review-media">
            <span class="chip"><x-ico name="play" style="width:16px;height:16px" /> Видеоотзыв</span>
        </div>
    @endif

    <p style="margin:0;color:var(--ink-soft)">{{ $review->body }}</p>

    @if ($review->published_at)
        <div class="muted" style="font-size:13px">{{ $review->published_at->translatedFormat('d F Y') }}</div>
    @endif
</article>
