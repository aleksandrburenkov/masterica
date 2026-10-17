@extends('layouts.site')

@section('title', 'Контакты — '.setting('studio.name', 'Корни').', '.setting('studio.city', 'Брянск'))
@section('meta_description', 'Адрес, телефон и часы работы студии «'.setting('studio.name', 'Корни').'» в '.setting('studio.city', 'Брянске').'.')
@section('canonical', route('contacts'))

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => 'Контакты']]" />
@endsection

@push('structured')
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => setting('studio.name', 'Корни'),
            'telephone' => setting('studio.phone'),
            'address' => ['@type' => 'PostalAddress', 'streetAddress' => setting('studio.address'), 'addressLocality' => setting('studio.city'), 'addressCountry' => 'RU'],
            'openingHours' => setting('studio.hours'),
            'url' => route('contacts'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
@endpush

@section('content')
    <section class="section section--tight">
        <div class="container">
            <div class="section-head" style="text-align:left;margin-left:0">
                <h1>Контакты</h1>
                <p>Мы рады ответить на вопросы о занятиях, работах и курсах.</p>
            </div>

            <div class="detail" style="grid-template-columns:1fr 1fr">
                <div class="card" data-reveal>
                    <div class="stack">
                        <div class="feature"><span class="feature__icon"><x-ico name="phone" /></span><div><h4>Телефон</h4><p><a href="tel:{{ preg_replace('/[^0-9+]/', '', setting('studio.phone', '')) }}">{{ setting('studio.phone', '+7 (906) 698-31-06') }}</a></p></div></div>
                        <div class="feature"><span class="feature__icon"><x-ico name="pin" /></span><div><h4>Адрес</h4><p>{{ setting('studio.address', 'г. Брянск, ул. Московская, д. 23') }}</p></div></div>
                        <div class="feature"><span class="feature__icon"><x-ico name="clock" /></span><div><h4>Часы работы</h4><p>{{ setting('studio.hours', 'Ежедневно 10:00–19:00') }}</p></div></div>
                        <div class="feature"><span class="feature__icon"><x-ico name="mail" /></span><div><h4>Почта студии</h4><p>{{ setting('studio.email', 'info@korni.local') }}</p></div></div>
                    </div>
                </div>

                <div class="card" data-reveal>
                    <h3>Напишите нам</h3>
                    <p class="muted">Оставьте заявку — студия ответит удобным способом.</p>
                    <form action="{{ route('outreach.store') }}" method="post">
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
