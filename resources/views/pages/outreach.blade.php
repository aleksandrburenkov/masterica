@extends('layouts.site')

@section('title', 'Выездные мастер-классы в '.setting('studio.city', 'Брянске').' — запрос и расчёт')
@section('meta_description', 'Выездной мастер-класс для группы: расчёт стоимости, форма запроса на дату и место. '.money((int) setting('pricing.outreach_base_cents', 800000)).' за группу до '.setting('pricing.outreach_included', 8).' участников.')
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
                    <p class="lead">Для праздника, школы, детского сада или компании. Калькулятор показывает расчёт сразу, а студия подтверждает детали.</p>
                    <a class="btn" href="#calculator">Рассчитать стоимость</a>
                </div>
                <div class="hero__media"><img src="{{ asset('images/photo-3.jpg') }}" alt="Выездной мастер-класс" loading="lazy"></div>
            </div>
        </div>
    </section>

    <section class="section section--tight" id="calculator" style="scroll-margin-top:140px">
        <div class="container">
            <div class="section-head">
                <h2>Расчёт стоимости</h2>
                <p>{{ money((int) setting('pricing.outreach_base_cents', 800000)) }} за группу до {{ setting('pricing.outreach_included', 8) }} участников; каждый дополнительный участник — {{ money((int) setting('pricing.outreach_extra_cents', 70000)) }}. Продолжительность — {{ (int) (setting('pricing.outreach_duration_minutes', 120) / 60) }} часа.</p>
            </div>

            <div class="calc">
                <form class="card" action="{{ route('outreach.store') }}" method="post" data-outreach
                      data-base="{{ (int) setting('pricing.outreach_base_cents', 800000) }}"
                      data-included="{{ (int) setting('pricing.outreach_included', 8) }}"
                      data-extra="{{ (int) setting('pricing.outreach_extra_cents', 70000) }}"
                      data-currency="{{ currency_symbol(setting('rules.currency', 'RUB')) }}"
                      novalidate>
                    @csrf
                    <h3>Параметры занятия</h3>

                    <div class="field">
                        <label for="o-direction">Направление</label>
                        <select id="o-direction" class="select @error('direction') is-invalid @enderror" name="direction" required>
                            <option value="">Выберите направление</option>
                            @foreach ($directions as $direction)
                                <option value="{{ $direction }}" @selected(old('direction') === $direction)>{{ $direction }}</option>
                            @endforeach
                            <option value="Свободная тема" @selected(old('direction') === 'Свободная тема')>Свободная тема</option>
                        </select>
                        @error('direction')<div class="error-text">{{ $message }}</div>@enderror
                    </div>

                    <div class="grid grid-2" style="gap:14px">
                        <div class="field">
                            <label for="o-date">Дата</label>
                            <input id="o-date" class="input" type="date" name="requested_date" value="{{ old('requested_date', now()->addWeek()->toDateString()) }}" min="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="field">
                            <label for="o-participants">Число участников</label>
                            <input id="o-participants" class="input" type="number" name="participants_count" min="1" max="60" value="{{ old('participants_count', 8) }}" data-outreach-participants required>
                        </div>
                    </div>

                    <div class="grid grid-2" style="gap:14px">
                        <div class="field">
                            <label for="o-location">Место проведения</label>
                            <input id="o-location" class="input" type="text" name="location" value="{{ old('location', setting('studio.city', 'Брянск')) }}" required>
                        </div>
                        <div class="field">
                            <label for="o-age">Возрастная группа</label>
                            <input id="o-age" class="input" type="text" name="age_group" value="{{ old('age_group', 'Дети 7–12 лет') }}">
                        </div>
                    </div>

                    <h3 style="margin-top:10px">Контакт взрослого</h3>
                    <div class="field"><label for="o-name">Имя</label><input id="o-name" class="input" type="text" name="contact_name" value="{{ old('contact_name', auth()->user()?->name) }}" required></div>
                    <div class="grid grid-2" style="gap:14px">
                        <div class="field"><label for="o-phone">Телефон</label><input id="o-phone" class="input" type="text" name="contact_phone" value="{{ old('contact_phone', auth()->user()?->phone) }}" required></div>
                        <div class="field"><label for="o-email">Электронная почта</label><input id="o-email" class="input" type="email" name="contact_email" value="{{ old('contact_email', auth()->user()?->email) }}" required></div>
                    </div>
                    <div class="field"><label for="o-comment">Комментарий</label><textarea id="o-comment" class="textarea" name="comment" placeholder="Пожелания по программе, площадке и времени">{{ old('comment') }}</textarea></div>

                    <div class="field">
                        <label class="checkbox"><input type="checkbox" name="consent_data" value="1" required><span>Согласен(на) на обработку данных для ответа на запрос</span></label>
                        <label class="checkbox" style="margin-top:8px"><input type="checkbox" name="consent_marketing" value="1"><span>Новости и предложения — необязательно</span></label>
                    </div>

                    <button class="btn btn--block" type="submit">Отправить запрос</button>
                    <p class="form-note" style="margin-top:14px">Это запрос и расчёт, а не оплата. Итоговую стоимость студия подтверждает до заказа.</p>
                </form>

                <div class="calc__result" data-reveal>
                    <div style="opacity:.9">Предварительная стоимость</div>
                    <div class="amount" data-outreach-total>{{ money($calc['total_cents']) }}</div>
                    <div class="calc__breakdown">
                        <div><span>Группа до {{ $calc['included_participants'] }} участников</span><span>{{ money($calc['base_price_cents']) }}</span></div>
                        <div><span>Дополнительные участники</span><span data-outreach-extra>0 {{ currency_symbol(setting('rules.currency', 'RUB')) }}</span></div>
                        <div><span>Продолжительность</span><span>{{ (int) (setting('pricing.outreach_duration_minutes', 120) / 60) }} часа</span></div>
                    </div>
                    <p style="margin:16px 0 0;opacity:.9;font-size:14px">Расчёт обновляется при изменении числа участников.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section" style="background:rgba(255,255,255,.5)">
        <div class="container">
            <div class="section-head"><h2>Частые вопросы</h2></div>
            <div style="max-width:900px;margin:0 auto">
                <x-faq :items="[
                    ['q' => 'Сколько участников можно пригласить?', 'a' => 'От 1 до 60. Базовая стоимость — за группу до '.setting('pricing.outreach_included', 8).' участников, далее — за каждого дополнительного.'],
                    ['q' => 'Вы выезжаете за пределы города?', 'a' => 'Уточните место при оформлении заявки — студия подтвердит возможность и условия.'],
                    ['q' => 'Материалы входят?', 'a' => 'Да, материалы для выездного занятия студия привозит с собой.'],
                ]" />
            </div>
        </div>
    </section>
@endsection
