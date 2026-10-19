@extends('layouts.site')

@php $firstSession = $sessions->first(); @endphp

@section('title', $masterclass->seo_title ?: ($masterclass->title.' — мастер-класс в '.setting('studio.city', 'Брянске')))
@section('meta_description', $masterclass->seo_description ?: $masterclass->short_description)
@section('canonical', route('masterclasses.show', $masterclass->slug))
@section('og_type', 'article')
@section('og_image', asset($masterclass->image ?? 'images/hero-masterclass.png'))

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => 'Мастер-классы', 'url' => route('masterclasses.index')], ['label' => $masterclass->title]]" />
@endsection

@push('structured')
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $masterclass->title,
            'description' => $masterclass->short_description,
            'image' => asset($masterclass->image ?? 'images/hero-masterclass.png'),
            'offers' => [
                '@type' => 'Offer',
                'price' => (string) ($masterclass->price_cents / 100),
                'priceCurrency' => $masterclass->currency,
                'availability' => 'https://schema.org/InStock',
                'url' => route('masterclasses.show', $masterclass->slug),
            ],
        ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
@endpush

@section('content')
    <section class="section section--tight">
        <div class="container">
            <div class="detail">
                <div>
                    <div class="detail__media">
                        <img src="{{ asset($masterclass->image ?? 'images/hero-masterclass.png') }}" alt="{{ $masterclass->title }}">
                    </div>
                </div>

                <div data-reveal>
                    <span class="chip">{{ $masterclass->technique?->name ?? 'Мастер-класс' }}</span>
                    <h1 style="margin-top:14px">{{ $masterclass->title }}</h1>
                    <p class="lead-muted">{{ $masterclass->short_description }}</p>

                    <div class="detail__price">
                        <span class="price" style="font-size:34px">{{ money($masterclass->price_cents) }}</span>
                        <span class="muted">за участника · материалы {{ $masterclass->materials_included ? 'включены' : 'не включены' }}</span>
                    </div>

                    <div class="fact-grid">
                        <div class="fact"><div class="fact__label">Возраст</div><div class="fact__value">{{ $masterclass->ageLabel() }}</div></div>
                        <div class="fact"><div class="fact__label">Длительность</div><div class="fact__value">{{ $masterclass->durationLabel() }}</div></div>
                        <div class="fact"><div class="fact__label">Уровень</div><div class="fact__value">{{ $masterclass->level === 'beginner' ? 'Начинающий' : 'Продолжающий' }}</div></div>
                        <div class="fact"><div class="fact__label">Формат</div><div class="fact__value">{{ $masterclass->format === 'studio' ? 'В студии' : 'Выездной' }}</div></div>
                    </div>

                    @if ($masterclass->description)
                        <h3>О занятии</h3>
                        <p>{{ $masterclass->description }}</p>
                    @endif

                    @if ($masterclass->outcome)
                        <h3>Результат</h3>
                        <ul class="list-check">
                            <li><x-ico name="check-circle" /><span>{{ $masterclass->outcome }}</span></li>
                            <li><x-ico name="check-circle" /><span>Материалы {{ $masterclass->materials_included ? 'предоставляет студия' : 'нужно принести с собой' }}.</span></li>
                        </ul>
                    @endif

                    <div class="note-strip" style="margin-top:20px">
                        {{ setting('rules.cancel_policy') }} Место резервируется на {{ setting('rules.hold_minutes', '15') }} минут во время оформления.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section" style="background:rgba(255,255,255,.5)">
        <div class="container">
            <div class="section-head">
                <h2>Опубликованные сеансы</h2>
                <p>Выберите удобную дату. Часовой пояс сеансов — Europe/Moscow.</p>
            </div>

            @if ($sessions->isEmpty())
                <div class="empty" style="max-width:760px;margin:0 auto">
                    <x-ico name="calendar" />
                    <h3>Свободных сеансов пока нет</h3>
                    <p>Даты обновляются в расписании студии. Оставьте запрос — и мы сообщим о новых датах.</p>
                    <a class="btn btn--ghost" href="{{ route('masterclasses.index') }}#request">Оставить запрос</a>
                </div>
            @else
                <div class="detail" style="grid-template-columns:1.1fr .9fr">
                    <div class="session-list">
                        @foreach ($sessions as $session)
                            @include('partials.session-item', ['session' => $session])
                        @endforeach
                    </div>

                    <div class="card sticky-form" id="booking">
                        <h3>Запись на занятие</h3>
                        @guest
                            <p class="muted">Регистрация не нужна: заполните контактные данные, и мы подтвердим запись.</p>
                        @endguest
                        <form action="{{ route('bookings.store', $masterclass->slug) }}" method="post" data-ajax-form novalidate>
                            @csrf
                            <div class="field">
                                <label for="session">Сеанс</label>
                                <select id="session" class="select @error('workshop_session_id') is-invalid @enderror" name="workshop_session_id" required>
                                    @foreach ($sessions as $session)
                                        @php $local = $session->starts_at->copy()->setTimezone($session->timezone); @endphp
                                        <option value="{{ $session->id }}" @selected((int) old('workshop_session_id') === $session->id) @disabled($session->seatsLeft() <= 0)>
                                            {{ $local->translatedFormat('d M, l') }} в {{ $local->format('H:i') }} — свободно {{ $session->seatsLeft() }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('workshop_session_id')<div class="error-text">{{ $message }}</div>@enderror
                            </div>
                            <div class="field">
                                <label for="participants">Число участников</label>
                                <input id="participants" class="input @error('participants_count') is-invalid @enderror" type="number" name="participants_count" min="1" max="8" value="{{ old('participants_count', auth()->check() ? 1 : '') }}" placeholder="Укажите количество участников" required>
                                @error('participants_count')<div class="error-text">{{ $message }}</div>@enderror
                            </div>
                            <div class="field">
                                <label for="b-name">Контактное имя взрослого</label>
                                <input id="b-name" class="input @error('contact_name') is-invalid @enderror" type="text" name="contact_name" value="{{ old('contact_name', auth()->user()?->name) }}" placeholder="Как к вам обращаться" required>
                                @error('contact_name')<div class="error-text">{{ $message }}</div>@enderror
                            </div>
                            <div class="grid grid-2" style="gap:14px">
                                <div class="field">
                                    <label for="b-phone">Телефон</label>
                                    <input id="b-phone" class="input @error('contact_phone') is-invalid @enderror" type="text" name="contact_phone" value="{{ old('contact_phone', auth()->user()?->phone) }}" placeholder="+7 (___) ___-__-__" required>
                                    @error('contact_phone')<div class="error-text">{{ $message }}</div>@enderror
                                </div>
                                <div class="field">
                                    <label for="b-email">Электронная почта</label>
                                    <input id="b-email" class="input @error('contact_email') is-invalid @enderror" type="email" name="contact_email" value="{{ old('contact_email', auth()->user()?->email) }}" placeholder="name@example.com" required>
                                    @error('contact_email')<div class="error-text">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="field">
                                <label for="b-age">Возраст ребёнка (если нужно)</label>
                                <input id="b-age" class="input" type="number" name="child_age" min="0" max="99" value="{{ old('child_age') }}" placeholder="Необязательно">
                                <div class="hint">Указывайте только минимально необходимое для занятия.</div>
                            </div>
                            <div class="field">
                                <label class="checkbox">
                                    <input type="checkbox" name="consent_data" value="1" required>
                                    <span>
                                        {{ legal('consent_booking', 'lead', 'Даю согласие на обработку персональных данных для оформления записи на мастер-класс') }}.
                                        <a href="{{ route('legal.show', legal('consent_personal_data', 'slug', 'soglasie-na-obrabotku-dannyh')) }}" target="_blank" rel="noopener">Условия обработки</a>
                                    </span>
                                </label>
                                @error('consent_data')<div class="error-text">{{ $message }}</div>@enderror
                            </div>
                            <button class="btn btn--block" type="submit">Записаться</button>
                            <p class="form-note" style="margin-top:14px">После отправки запись подтверждается; оплата — в студии. Порядок отмены и переноса — в <a href="{{ route('legal.show', legal('booking_rules', 'slug', 'pravila-zapisi-i-zakaza')) }}" target="_blank" rel="noopener">Правилах записи и заказа</a>. Данные ребёнка обрабатываются на основании согласия законного представителя.</p>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </section>

    @if ($reviews->isNotEmpty())
        <section class="section">
            <div class="container">
                <div class="section-head"><h2>Отзывы об этом направлении</h2></div>
                <div class="grid grid-3">
                    @foreach ($reviews as $review)
                        @include('partials.review-card', ['review' => $review])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($related->isNotEmpty())
        <section class="section" style="background:rgba(255,255,255,.5)">
            <div class="container">
                <div class="section-head"><h2>Другие направления</h2></div>
                <div class="grid grid-3">
                    @foreach ($related as $item)
                        @include('partials.masterclass-card', ['masterclass' => $item])
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
