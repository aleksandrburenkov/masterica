@extends('layouts.site')

@php
    $studioName = setting('studio.name', 'Корни');
    $studioTagline = setting('studio.tagline', 'Арт-студия «Корни»');
    $studioCity = setting('studio.city', 'Брянск');
    $studioAddress = setting('studio.address', 'г. Брянск, ул. Московская, д. 23');
    $studioHours = setting('studio.hours', 'Ежедневно 10:00–19:00');
    $studioPhone = setting('studio.phone', '+7 (906) 698-31-06');
    $studioEmail = setting('studio.email', 'info@korni.local');
    $mapLat = setting('studio.latitude', '53.216894');
    $mapLng = setting('studio.longitude', '34.348181');
    $mapZoom = setting('studio.map_zoom', '17');
    $yandexMapsKey = config('services.yandex_maps.key');
@endphp

@section('title', 'Контакты — '.$studioName.', '.$studioCity)
@section('meta_description', 'Адрес, телефон и часы работы студии «'.$studioName.'» в '.$studioCity.'.')
@section('canonical', route('contacts'))

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => 'Контакты']]" />
@endsection

@push('head')
    <script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU{{ $yandexMapsKey ? '&apikey='.urlencode($yandexMapsKey) : '' }}" type="text/javascript"></script>
    <link rel="stylesheet" href="{{ asset('css/site/modules/studio-map.css') }}">
@endpush

@push('structured')
    @php
        $localBusiness = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => $studioName,
            'telephone' => setting('studio.phone'),
            'address' => ['@type' => 'PostalAddress', 'streetAddress' => $studioAddress, 'addressLocality' => setting('studio.city'), 'addressCountry' => 'RU'],
            'openingHours' => $studioHours,
            'url' => route('contacts'),
        ];

        if (is_numeric($mapLat) && is_numeric($mapLng)) {
            $localBusiness['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float) $mapLat, 'longitude' => (float) $mapLng];
        }
    @endphp
    <script type="application/ld+json">
        {!! json_encode($localBusiness, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
@endpush

@section('content')
    <section class="section section--tight">
        <div class="container">
            <div class="section-head" style="text-align:left;margin-left:0">
                <h1>Контакты</h1>
                <p>Мы рады ответить на вопросы о занятиях, работах и курсах.</p>
            </div>

            <div class="detail detail--contacts">
                <div class="contact-side">
                    <div class="card" data-reveal>
                        <div class="stack">
                            <div class="feature"><span class="feature__icon"><x-ico name="phone" /></span><div><h4>Телефон</h4><p><a href="tel:{{ preg_replace('/[^0-9+]/', '', $studioPhone) }}">{{ $studioPhone }}</a></p></div></div>
                            <div class="feature"><span class="feature__icon"><x-ico name="pin" /></span><div><h4>Адрес</h4><p>{{ $studioAddress }}</p></div></div>
                            <div class="feature"><span class="feature__icon"><x-ico name="clock" /></span><div><h4>Часы работы</h4><p>{{ $studioHours }}</p></div></div>
                            <div class="feature"><span class="feature__icon"><x-ico name="mail" /></span><div><h4>Почта студии</h4><p>{{ $studioEmail }}</p></div></div>
                        </div>
                    </div>

                    <div class="card card--pad-0 studio-map" data-reveal data-studio-map
                        data-lat="{{ $mapLat }}" data-lng="{{ $mapLng }}" data-zoom="{{ $mapZoom }}"
                        data-icon="{{ asset('images/studio-map-pin.svg') }}"
                        data-title="{{ $studioTagline }}" data-address="{{ $studioAddress }}" data-hours="{{ $studioHours }}">
                        <div class="studio-map__canvas" id="studio-map" data-studio-map-canvas role="region"
                            aria-label="Карта проезда к студии «{{ $studioName }}» по адресу {{ $studioAddress }}">
                            <div class="studio-map__fallback" aria-hidden="true">
                                <span class="studio-map__fallback-pin"><x-ico name="pin" /></span>
                                <span>{{ $studioAddress }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card" data-reveal>
                    <h3>Напишите нам</h3>
                    <p class="muted">Оставьте заявку — студия ответит удобным способом.</p>
                    <form action="{{ route('outreach.store') }}" method="post" data-ajax-form>
                        @csrf
                        <input type="hidden" name="direction" value="Вопрос со страницы контактов">
                        <input type="hidden" name="requested_date" value="{{ now()->addWeek()->toDateString() }}">
                        <input type="hidden" name="location" value="{{ setting('studio.address') }}">
                        <input type="hidden" name="age_group" value="Взрослый">
                        <input type="hidden" name="participants_count" value="1">
                        <div class="field"><label for="ct-name">Имя</label><input id="ct-name" class="input" type="text" name="contact_name" value="{{ old('contact_name', auth()->user()?->name) }}" required></div>
                        <div class="field"><label for="ct-phone">Телефон</label><input id="ct-phone" class="input" type="text" name="contact_phone" value="{{ old('contact_phone', auth()->user()?->phone) }}" required></div>
                        <div class="field"><label for="ct-email">Электронная почта</label><input id="ct-email" class="input" type="email" name="contact_email" value="{{ old('contact_email', auth()->user()?->email) }}" required></div>
                        <div class="field"><label for="ct-comment">Сообщение</label><textarea id="ct-comment" class="textarea" name="comment" required>{{ old('comment') }}</textarea></div>
                        <label class="checkbox" style="margin-bottom:14px">
                            <input type="checkbox" name="consent_data" value="1" required>
                            <span>
                                {{ legal('consent_contacts', 'lead', 'Даю согласие на обработку персональных данных для рассмотрения обращения и направления ответа') }}.
                                <a href="{{ route('legal.show', legal('consent_personal_data', 'slug', 'soglasie-na-obrabotku-dannyh')) }}" target="_blank" rel="noopener">Условия обработки</a>
                            </span>
                        </label>
                        <button class="btn btn--block" type="submit">Отправить</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('js/site/modules/studio-map.js') }}" defer></script>
@endpush
