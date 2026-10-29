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

    @php($video = $review->video ?? null)
    @if ($video && $video->isReady())
        <div class="review-card__video" data-review-video>
            <video class="review-card__video-el" data-review-player controls playsinline preload="none"
                   @if ($video->posterUrl()) poster="{{ $video->posterUrl() }}" @endif>
                <source src="{{ $video->streamUrl() }}" type="{{ $video->mime_type ?: 'video/mp4' }}">
            </video>

            <button type="button" class="review-card__cover" data-review-cover
                    aria-label="Смотреть видеоотзыв"
                    @if ($video->posterUrl()) style="background-image:url('{{ $video->posterUrl() }}')" @endif>
                <span class="review-card__play"><x-ico name="play" /></span>
            </button>

            <div class="review-preloader" data-review-preloader hidden>
                <span class="review-preloader__loom" aria-hidden="true">
                    <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="32" cy="32" r="25" stroke="currentColor" stroke-width="2" opacity=".18" />
                        <path d="M32 7c11 5 17 14 17 25s-6 20-17 25" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" />
                        <path d="M32 57C21 52 15 43 15 32s6-20 17-25" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" opacity=".55" />
                        <path d="M32 20c5 3 7 7 7 12s-2 9-7 12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" opacity=".8" />
                        <circle cx="32" cy="7" r="3.2" fill="currentColor" />
                    </svg>
                </span>
                <span class="review-preloader__text">Арт-студия {{ setting('studio.name', 'Корни') }}</span>
            </div>
        </div>
    @elseif ($review->type->value === 'video')
        <div class="review-media">
            @if ($review->video_path)
                <a class="chip" href="{{ $review->video_path }}" target="_blank" rel="noopener nofollow"><x-ico name="play" style="width:16px;height:16px" /> Видеоотзыв</a>
            @else
                <span class="chip"><x-ico name="play" style="width:16px;height:16px" /> Видеоотзыв</span>
            @endif
        </div>
    @endif

    <p style="margin:0;color:var(--ink-soft)">{{ $review->body }}</p>

    @if ($review->published_at)
        <div class="muted" style="font-size:13px">{{ $review->published_at->translatedFormat('d F Y') }}</div>
    @endif
</article>
