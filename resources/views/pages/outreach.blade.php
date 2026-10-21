@extends('layouts.site')

@section('title', 'Выездные мастер-классы в ' . setting('studio.city', 'Брянске') . ' — запрос и расчёт')
@section('meta_description', 'Выездной мастер-класс для группы: расчёт стоимости по выбранному мастер-классу и
    количеству участников, форма запроса на дату и место.')
@section('canonical', route('outreach.index'))

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => 'Выездные мастер-классы']]" />
@endsection

@section('content')
    <section class="hero">
        <div class="container">
            <div class="hero__inner">
                <div class="hero__content" data-reveal>
                    <h1>Выездные мастер-классы</h1>
                    <p class="lead">Для праздника, школы, детского сада или компании. Калькулятор показывает расчёт сразу,
                        а студия подтверждает детали.</p>
                    <a class="btn" href="#calculator">Рассчитать стоимость</a>
                </div>
            </div>
        </div>
    </section>

    <section class="section section--tight" id="calculator" style="scroll-margin-top:140px">
        <div class="container">
            <div class="section-head">
                <h2>Расчёт стоимости</h2>
                <p>Выберите мастер-класс и количество участников — стоимость пересчитается автоматически. Продолжительность
                    указана в карточке мастер-класса.</p>
            </div>

            <div class="calc">
                <form class="card" action="{{ route('outreach.store') }}" method="post" data-outreach
                    data-currency="{{ currency_symbol(setting('rules.currency', 'RUB')) }}" novalidate>
                    @csrf
                    <h3>Параметры занятия</h3>

                    <div class="field">
                        <label for="o-direction">Мастер-класс</label>
                        <select id="o-direction" class="select @error('direction') is-invalid @enderror" name="direction"
                            data-outreach-direction required>
                            <option value="">Выберите мастер-класс</option>
                            @foreach ($masterclasses as $masterclass)
                                <option value="{{ $masterclass->slug }}"
                                    data-price="{{ $masterclass->outreachPriceCents() }}"
                                    data-included="{{ (int) $masterclass->included_participants }}"
                                    data-duration="{{ (int) $masterclass->duration_minutes }}"
                                    data-surcharges="{{ json_encode($masterclass->participant_surcharges ?? []) }}"
                                    @selected(old('direction') === $masterclass->slug)>{{ $masterclass->title }}</option>
                            @endforeach
                            <option value="Свободная тема" @selected(old('direction') === 'Свободная тема')>Свободная тема</option>
                        </select>
                        @error('direction')
                            <div class="error-text">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid grid-2" style="gap:14px">
                        <div class="field">
                            <label for="o-date">Дата</label>
                            <input id="o-date" class="input" type="date" name="requested_date"
                                value="{{ old('requested_date', now()->addWeek()->toDateString()) }}"
                                min="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="field">
                            <label for="o-participants">Число участников</label>
                            <input id="o-participants" class="input" type="number" name="participants_count"
                                min="1" max="60" value="{{ old('participants_count') }}"
                                placeholder="Введите количество участников" data-outreach-participants required>
                        </div>
                    </div>

                    <div class="grid grid-2" style="gap:14px">
                        <div class="field">
                            <label for="o-location">Место проведения</label>
                            <input id="o-location" class="input" type="text" name="location"
                                value="{{ old('location') }}" placeholder="Введите город или район проведения мероприятия"
                                required>
                        </div>
                        <div class="field">
                            <label for="o-age">Возрастная группа</label>
                            <input id="o-age" class="input" type="text" name="age_group"
                                value="{{ old('age_group') }}" placeholder="Введите возраст, например: Дети 7-12 лет">
                        </div>
                    </div>

                    <h3 style="margin-top:10px">Контакт взрослого</h3>
                    <div class="field"><label for="o-name">Имя</label><input id="o-name" class="input" type="text"
                            name="contact_name" value="{{ old('contact_name', auth()->user()?->name) }}" required></div>
                    <div class="grid grid-2" style="gap:14px">
                        <div class="field"><label for="o-phone">Телефон</label><input id="o-phone" class="input"
                                type="text" name="contact_phone"
                                value="{{ old('contact_phone', auth()->user()?->phone) }}" required></div>
                        <div class="field"><label for="o-email">Электронная почта</label><input id="o-email"
                                class="input" type="email" name="contact_email"
                                value="{{ old('contact_email', auth()->user()?->email) }}" required></div>
                    </div>
                    <div class="field"><label for="o-comment">Комментарий</label>
                        <textarea id="o-comment" class="textarea" name="comment" placeholder="Пожелания по программе, площадке и времени">{{ old('comment') }}</textarea>
                    </div>

                    <div class="field">
                        <label class="checkbox">
                            <input type="checkbox" name="consent_data" value="1" required>
                            <span>
                                {{ legal('consent_outreach', 'lead', 'Даю согласие на обработку персональных данных для рассмотрения заявки и расчёта стоимости') }}.
                                <a href="{{ route('legal.show', legal('consent_personal_data', 'slug', 'soglasie-na-obrabotku-dannyh')) }}"
                                    target="_blank" rel="noopener">Условия обработки</a>
                            </span>
                        </label>
                        @error('consent_data')
                            <div class="error-text">{{ $message }}</div>
                        @enderror
                        <label class="checkbox" style="margin-top:8px">
                            <input type="checkbox" name="consent_marketing" value="1">
                            <span>{{ legal('consent_marketing', 'lead', 'Согласен(на) получать новости и предложения студии (необязательно)') }}</span>
                        </label>
                    </div>

                    <button class="btn btn--block" type="submit">Отправить запрос</button>
                    <p class="form-note" style="margin-top:14px">Это запрос и расчёт, а не заключённый договор. Договор
                        считается заключённым после подтверждения студии. Условия выезда и оплаты — в <a
                            href="{{ route('legal.show', legal('outreach_terms', 'slug', 'usloviya-vyezdnyh-master-klassov')) }}"
                            target="_blank" rel="noopener">Условиях оказания услуг</a>.</p>
                </form>

                <div class="calc__result" data-reveal>
                    <div style="opacity:.9">Предварительная стоимость</div>
                    <div class="amount" data-outreach-total>{{ money(0) }}</div>
                    <div class="calc__breakdown">
                        <div><span data-outreach-group-label>Группа 0 участников</span><span
                                data-outreach-base>{{ money(0) }}</span></div>
                        <div><span data-outreach-percent-label>% за увеличенное количество участников</span><span
                                data-outreach-percent>0 %</span></div>
                        <div><span>Продолжительность</span><span data-outreach-duration>0 часа</span></div>
                    </div>
                    <p style="margin:16px 0 0;opacity:.9;font-size:14px">Расчёт обновляется при изменении мастер-класса и
                        числа участников.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section" style="background:rgba(255,255,255,.5)">
        <div class="container">
            <div class="section-head">
                <h2>Частые вопросы</h2>
            </div>
            <div style="max-width:900px;margin:0 auto">
                <x-faq :items="[
                    [
                        'q' => 'Сколько участников можно пригласить?',
                        'a' =>
                            'От 1 до 60. В карточке мастер-класса указано количество участников, входящее в базовую стоимость; за превышение начисляется процент, указанный в карточке.',
                    ],
                    [
                        'q' => 'Вы выезжаете за пределы города?',
                        'a' => 'Уточните место при оформлении заявки — студия подтвердит возможность и условия.',
                    ],
                    ['q' => 'Материалы входят?', 'a' => 'Да, материалы для выездного занятия студия привозит с собой.'],
                ]" />
            </div>
        </div>
    </section>
@endsection
