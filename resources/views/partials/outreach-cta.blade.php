@php
    $ctaSessions = \App\Models\WorkshopSession::query()
        ->with('masterclass')
        ->where('is_published', true)
        ->where('status', \App\Enums\SessionStatus::Scheduled->value)
        ->where('starts_at', '>', now())
        ->orderBy('starts_at')
        ->get()
        ->filter(fn ($session) => $session->seatsLeft() > 0)
        ->take(12);
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

                <form action="{{ route('bookings.quick-store') }}" method="post" data-ajax-form>
                    @csrf
                    <input type="hidden" name="source" value="{{ request()->path() }}">

                    <div class="field">
                        <label for="req-name">Ваше имя</label>
                        <input id="req-name" class="input @error('contact_name') is-invalid @enderror" type="text" name="contact_name" value="{{ old('contact_name') }}" required>
                        @error('contact_name')<div class="error-text">{{ $message }}</div>@enderror
                    </div>

                    <div class="grid grid-2" style="gap:14px">
                        <div class="field">
                            <label for="req-phone">Телефон</label>
                            <input id="req-phone" class="input @error('contact_phone') is-invalid @enderror" type="tel" name="contact_phone" value="{{ old('contact_phone', auth()->user()?->phone) }}" required>
                            @error('contact_phone')<div class="error-text">{{ $message }}</div>@enderror
                        </div>
                        <div class="field">
                            <label for="req-email">Электронная почта</label>
                            <input id="req-email" class="input @error('contact_email') is-invalid @enderror" type="email" name="contact_email" value="{{ old('contact_email', auth()->user()?->email) }}" required>
                            @error('contact_email')<div class="error-text">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="field">
                        <label for="req-session">Мастер-класс и дата</label>
                        <select id="req-session" class="select @error('workshop_session_id') is-invalid @enderror" name="workshop_session_id" required @disabled($ctaSessions->isEmpty())>
                            <option value="">Выберите мастер-класс и удобную дату</option>
                            @foreach ($ctaSessions as $session)
                                <option value="{{ $session->id }}" @selected((string) old('workshop_session_id') === (string) $session->id)>
                                    {{ $session->masterclass?->title }} — {{ $session->starts_at->format('d.m.Y H:i') }} (свободно {{ $session->seatsLeft() }})
                                </option>
                            @endforeach
                        </select>
                        @error('workshop_session_id')<div class="error-text">{{ $message }}</div>@enderror
                        @if ($ctaSessions->isEmpty())
                            <p class="form-note" style="margin-top:8px">Свободных дат для записи пока нет. Свяжитесь со студией по телефону — подберём время.</p>
                        @endif
                    </div>

                    <div class="field">
                        <label for="req-participants">Участников</label>
                        <input id="req-participants" class="input" type="number" name="participants_count" min="1" max="8" value="{{ old('participants_count', 1) }}" required>
                    </div>

                    <div class="field">
                        <label for="req-comment">Комментарий</label>
                        <textarea id="req-comment" class="textarea" name="notes" rows="3" placeholder="Пожелания по занятию">{{ old('notes') }}</textarea>
                        @error('notes')<div class="error-text">{{ $message }}</div>@enderror
                    </div>

                    <div class="field">
                        <label class="checkbox">
                            <input type="checkbox" name="consent_data" value="1" required>
                            <span>
                                {{ legal('consent_contacts', 'lead', 'Даю согласие на обработку персональных данных для рассмотрения заявки и связи со мной') }}.
                                <a href="{{ route('legal.show', legal('consent_personal_data', 'slug', 'soglasie-na-obrabotku-dannyh')) }}" target="_blank" rel="noopener">Условия обработки</a>
                            </span>
                        </label>
                        @error('consent_data')<div class="error-text">{{ $message }}</div>@enderror
                        <label class="checkbox" style="margin-top:8px">
                            <input type="checkbox" name="consent_marketing" value="1">
                            <span>{{ legal('consent_marketing', 'lead', 'Согласен(на) получать новости и предложения студии (необязательно)') }}</span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn--block" @disabled($ctaSessions->isEmpty())>Записаться</button>
                    <p class="form-note" style="margin-top:14px">Заявка не является заключённым договором до её подтверждения студией. Порядок записи и оплаты — в <a href="{{ route('legal.show', legal('booking_rules', 'slug', 'pravila-zapisi-i-zakaza')) }}" target="_blank" rel="noopener">Правилах записи</a>.</p>
                </form>
            </div>
        </div>
    </div>
</section>
