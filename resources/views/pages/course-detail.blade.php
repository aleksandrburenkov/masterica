@extends('layouts.site')

@section('title', $course->seo_title ?: $course->title)
@section('meta_description', $course->seo_description ?: $course->short_description)
@section('canonical', route('courses.show', $course->slug))
@section('og_image', media_url($course->image, 'images/hero-masterclass.png'))

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => 'Онлайн-курсы', 'url' => route('courses.index')], ['label' => $course->title]]" />
@endsection

@push('structured')
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Course',
            'name' => $course->title,
            'description' => $course->short_description,
            'provider' => ['@type' => 'Organization', 'name' => setting('studio.name', 'Корни')],
            'offers' => ['@type' => 'Offer', 'price' => (string) ($course->price_cents / 100), 'priceCurrency' => $course->currency, 'category' => 'Онлайн-курс'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
@endpush

@section('content')
    <section class="section section--tight">
        <div class="container">
            <div class="detail">
                <div class="detail__media">
                    <img src="{{ media_url($course->image, 'images/hero-masterclass.png') }}" alt="{{ $course->title }}">
                </div>

                <div data-reveal>
                    <span class="chip">Онлайн-курс</span>
                    <h1 style="margin-top:14px">{{ $course->title }}</h1>
                    <p class="lead-muted">{{ $course->short_description }}</p>

                    <div class="detail__price">
                        <span class="price" style="font-size:34px">{{ money($course->price_cents) }}</span>
                        <span class="muted">предоставление права доступа на {{ (int) round($course->access_days / 30) }} месяцев</span>
                    </div>

                    <div class="fact-grid">
                        <div class="fact"><div class="fact__label">Автор</div><div class="fact__value">{{ $course->master?->name ?? 'Ирина Буренкова' }}</div></div>
                        <div class="fact"><div class="fact__label">Модулей</div><div class="fact__value">{{ $course->modules->count() }}</div></div>
                        <div class="fact"><div class="fact__label">Уроков</div><div class="fact__value">{{ $course->modules->sum(fn ($m) => $m->lessons->count()) }}</div></div>
                        <div class="fact"><div class="fact__label">Уровень</div><div class="fact__value">{{ $course->level === 'beginner' ? 'Начинающий' : 'Продолжающий' }}</div></div>
                    </div>

                    @if ($course->outcome)
                        <h3>Результат обучения</h3>
                        <ul class="list-check"><li><x-ico name="check-circle" /><span>{{ $course->outcome }}</span></li></ul>
                    @endif

                    <div style="margin-top:22px">
                        @auth
                            @if ($enrollment && $enrollment->hasActiveAccess())
                                @if ($firstLesson)
                                    <a class="btn btn--block" href="{{ route('courses.learn', [$course->slug, $firstLesson->id]) }}">Продолжить обучение</a>
                                    <div class="progress-bar" style="margin-top:14px"><span style="width:{{ $progress }}%"></span></div>
                                    <p class="muted" style="font-size:13px;margin-top:6px">Прогресс: {{ $progress }}%</p>
                                @else
                                    <div class="note-strip">Материалы курса ещё не опубликованы.</div>
                                @endif
                            @elseif ($enrollment)
                                <div class="note-strip" style="margin-bottom:14px">Заявка создана, доступ ожидает подтверждения оплаты администратором.</div>
                                <a class="btn btn--ghost btn--block" href="{{ route('account.courses') }}">Мои курсы</a>
                            @else
                                <form action="{{ route('courses.enroll', $course->slug) }}" method="post">
                                    @csrf
                                    <button class="btn btn--block" type="submit">Получить доступ к курсу</button>
                                </form>
                                <p class="form-note" style="margin-top:14px">Оформляя доступ, вы принимаете <a href="{{ route('legal.show', legal('license_terms', 'slug', 'licenzionnoe-soglashenie')) }}" target="_blank" rel="noopener">Лицензионное соглашение</a> и <a href="{{ route('legal.show', legal('platform_terms', 'slug', 'polzovatelskoe-soglashenie')) }}" target="_blank" rel="noopener">Пользовательское соглашение</a>. Оплата означает предоставление права доступа к материалам курса, а не передачу прав на контент. Доступ открывается после подтверждения оплаты.</p>
                            @endif
                        @else
                            <a class="btn btn--block" href="{{ route('login', ['redirect' => url()->current()]) }}">Войти и записаться</a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section" style="background:rgba(255,255,255,.5)">
        <div class="container">
            <div class="section-head"><h2>Программа курса</h2><p>Первый вводный урок открыт бесплатно.</p></div>
            <div style="max-width:900px;margin:0 auto" class="stack">
                @foreach ($course->modules as $module)
                    <div class="card">
                        <h3 style="margin-bottom:12px">{{ $module->title }}</h3>
                        @if ($module->description)<p class="muted" style="margin-top:0">{{ $module->description }}</p>@endif
                        <div class="lesson-nav" style="padding:0;box-shadow:none;border:0">
                            @foreach ($module->lessons as $lesson)
                                @php $unlocked = $lesson->is_preview || ($enrollment && $enrollment->hasActiveAccess()); @endphp
                                <div style="display:flex;align-items:center;gap:10px;padding:10px 6px;border-bottom:1px solid var(--line)">
                                    <span class="badge {{ $lesson->is_preview ? 'badge--violet' : 'badge--gray' }}">{{ $lesson->is_preview ? 'Бесплатно' : ($unlocked ? 'Доступно' : 'Платный') }}</span>
                                    <span style="flex:1">{{ $lesson->title }}</span>
                                    <span class="muted" style="font-size:13px">{{ $lesson->duration_minutes }} мин</span>
                                    @if (! $unlocked)
                                        <x-ico name="lock" style="width:16px;height:16px;color:var(--muted-2)" />
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
