@extends('layouts.site')

@section('title', 'Мастер-классы в Брянске — '.setting('studio.name', 'Корни'))
@section('meta_description', 'Мастер-классы в Брянске: адрес '.setting('studio.address').', телефон '.setting('studio.phone').'. Расписание, направления и запись онлайн.')
@section('canonical', route('masterclasses.bryansk'))

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => 'Мастер-классы в Брянске']]" />
@endsection

@push('structured')
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => setting('studio.name', 'Корни'),
            'description' => 'Мастер-классы для детей и взрослых в Брянске',
            'telephone' => setting('studio.phone'),
            'address' => ['@type' => 'PostalAddress', 'streetAddress' => setting('studio.address'), 'addressLocality' => setting('studio.city'), 'addressCountry' => 'RU'],
            'openingHours' => setting('studio.hours'),
            'url' => route('masterclasses.bryansk'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
    @if ($sessions->isNotEmpty())
        <script type="application/ld+json">
            {!! json_encode(array_map(function ($session) {
                $local = $session->starts_at->copy()->setTimezone($session->timezone);
                return [
                    '@context' => 'https://schema.org',
                    '@type' => 'Event',
                    'name' => $session->masterclass->title.' — '.setting('studio.name', 'Корни'),
                    'startDate' => $local->toIso8601String(),
                    'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
                    'location' => ['@type' => 'Place', 'name' => $session->location_name, 'address' => $session->address],
                    'offers' => ['@type' => 'Offer', 'price' => (string) ($session->priceCents() / 100), 'priceCurrency' => 'RUB', 'availability' => $session->seatsLeft() > 0 ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut'],
                ];
            }, $sessions->all()), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
        </script>
    @endif
@endpush

@section('content')
    <section class="hero">
        <div class="container">
            <div class="hero__inner">
                <div class="hero__content" data-reveal>
                    <h1>Мастер-классы в Брянске</h1>
                    <p class="lead">Творческие занятия в студии по адресу {{ setting('studio.address') }} и на выезде. Телефон: {{ setting('studio.phone') }}.</p>
                    <a class="btn" href="{{ route('masterclasses.index') }}">Выбрать занятие</a>
                </div>
            </div>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container">
            <div class="grid grid-4">
                <div class="card feature" data-reveal><span class="feature__icon"><x-ico name="pin" /></span><div><h4>Адрес</h4><p>{{ setting('studio.address') }}</p></div></div>
                <div class="card feature" data-reveal><span class="feature__icon"><x-ico name="phone" /></span><div><h4>Телефон</h4><p>{{ setting('studio.phone') }}</p></div></div>
                <div class="card feature" data-reveal><span class="feature__icon"><x-ico name="clock" /></span><div><h4>Часы работы</h4><p>{{ setting('studio.hours') }}</p></div></div>
                <div class="card feature" data-reveal><span class="feature__icon"><x-ico name="palette" /></span><div><h4>Направления</h4><p>{{ $masterclasses->count() }} техник для детей и взрослых</p></div></div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-head"><h2>Направления в Брянске</h2><p>Единое расписание и цены для студии.</p></div>
            <div class="grid grid-3">
                @foreach ($masterclasses as $masterclass)
                    @include('partials.masterclass-card', ['masterclass' => $masterclass])
                @endforeach
            </div>
        </div>
    </section>

    @if ($sessions->isNotEmpty())
        <section class="section" style="background:rgba(255,255,255,.5)">
            <div class="container">
                <div class="section-head"><h2>Ближайшие сеансы в Брянске</h2><p>Часовой пояс — Europe/Moscow, место — студия.</p></div>
                <div class="session-list" style="max-width:900px;margin:0 auto">
                    @foreach ($sessions as $session)
                        @include('partials.session-item', ['session' => $session])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="section">
        <div class="container">
            <div class="section-head"><h2>Как нас найти</h2></div>
            <div class="card" style="padding:0;overflow:hidden">
                <div style="background:var(--grad-soft);min-height:260px;display:grid;place-items:center;text-align:center;padding:40px">
                    <div>
                        <x-ico name="pin" style="width:42px;height:42px;color:var(--violet)" />
                        <h3 style="margin-top:12px">{{ setting('studio.address') }}</h3>
                        <p class="muted" style="margin:0">Точная схема проезда и карта согласуются с владельцем перед публикацией.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
