@php
    $directions = \App\Models\Masterclass::query()->published()->orderBy('sort')->pluck('title');
@endphp

<section class="section" id="request">
    <div class="container">
        <div class="cta-block" data-reveal>
            <div>
                <span class="chip chip--violet">Арт-студия «{{ setting('studio.name', 'Корни') }}»</span>
                <h2 style="margin-top:16px">Подскажем, с чего начать</h2>
                <p class="lead-muted">Расскажите, что хотите создать — студия уточнит формат и ответит удобным способом.</p>
                <div class="cta-block__media" style="margin-top:20px">
                    <img src="{{ asset('images/illustration.png') }}" alt="Иллюстрация творческих материалов" loading="lazy">
                </div>
                <div class="links">
                    <a href="{{ route('masterclasses.index') }}">Мастер-классы</a>
                    <a href="{{ route('courses.index') }}">Онлайн-курсы</a>
                    <a href="{{ route('shop.index') }}">Работы студии</a>
                </div>
            </div>

            <div class="card">
                <span class="chip">Связаться со студией</span>
                <h3 style="margin-top:12px">Оставить заявку</h3>
                <p class="lead-muted" style="font-size:14px">Контакт взрослого участника.</p>

                <form action="{{ route('outreach.store') }}" method="post" data-outreach
                      data-base="{{ (int) setting('pricing.outreach_base_cents', 800000) }}"
                      data-included="{{ (int) setting('pricing.outreach_included', 8) }}"
                      data-extra="{{ (int) setting('pricing.outreach_extra_cents', 70000) }}"
                      data-currency="{{ currency_symbol(setting('rules.currency', 'RUB')) }}">
                    @csrf
                    <input type="hidden" name="source" value="{{ request()->path() }}">

                    <div class="field">
                        <label for="req-name">Ваше имя</label>
                        <input id="req-name" class="input @error('contact_name') is-invalid @enderror" type="text" name="contact_name" value="{{ old('contact_name') }}" required>
                        @error('contact_name')<div class="error-text">{{ $message }}</div>@enderror
                    </div>

                    <div class="field">
                        <label for="req-phone">Телефон или электронная почта</label>
                        <input id="req-phone" class="input @error('contact_phone') is-invalid @enderror" type="text" name="contact_phone" value="{{ old('contact_phone') }}" required>
                        @error('contact_phone')<div class="error-text">{{ $message }}</div>@enderror
                    </div>
                    <input type="hidden" name="contact_email" value="{{ old('contact_email', 'zayavka@korni.local') }}">

                    <div class="field">
                        <label for="req-direction">Направление</label>
                        <select id="req-direction" class="select @error('direction') is-invalid @enderror" name="direction" required>
                            <option value="">Выберите интересующее направление</option>
                            @foreach ($directions as $direction)
                                <option value="{{ $direction }}" @selected(old('direction') === $direction)>{{ $direction }}</option>
                            @endforeach
                            <option value="Выездной мастер-класс">Выездной мастер-класс</option>
                        </select>
                        @error('direction')<div class="error-text">{{ $message }}</div>@enderror
                    </div>

                    <div class="grid grid-2" style="gap:14px">
                        <div class="field">
                            <label for="req-date">Желаемая дата</label>
                            <input id="req-date" class="input" type="date" name="requested_date" value="{{ old('requested_date', now()->addWeek()->toDateString()) }}" min="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="field">
                            <label for="req-participants">Участников</label>
                            <input id="req-participants" class="input" type="number" name="participants_count" min="1" max="60" value="{{ old('participants_count', 8) }}" data-outreach-participants required>
                        </div>
                    </div>
                    <input type="hidden" name="location" value="{{ old('location', 'г. Брянск') }}">
                    <input type="hidden" name="age_group" value="{{ old('age_group', 'Дети и взрослые') }}">

                    <div class="field">
                        <label class="checkbox">
                            <input type="checkbox" name="consent_data" value="1" required>
                            <span>
                                {{ legal('consent_outreach', 'lead', 'Даю согласие на обработку персональных данных для рассмотрения заявки и расчёта стоимости') }}.
                                <a href="{{ route('legal.show', legal('consent_personal_data', 'slug', 'soglasie-na-obrabotku-dannyh')) }}" target="_blank" rel="noopener">Условия обработки</a>
                            </span>
                        </label>
                        @error('consent_data')<div class="error-text">{{ $message }}</div>@enderror
                        <label class="checkbox" style="margin-top:8px">
                            <input type="checkbox" name="consent_marketing" value="1">
                            <span>{{ legal('consent_marketing', 'lead', 'Согласен(на) получать новости и предложения студии (необязательно)') }}</span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn--block">Отправить запрос</button>
                    <p class="form-note" style="margin-top:14px">Заявка не является заключённым договором до её подтверждения студией. Условия выезда — в <a href="{{ route('legal.show', legal('outreach_terms', 'slug', 'usloviya-vyezdnyh-master-klassov')) }}" target="_blank" rel="noopener">Условиях оказания услуг</a>.</p>
                </form>
            </div>
        </div>
    </div>
</section>
