@php
    // Вкладка, которую нужно показать. После неудачной отправки форма сообщает
    // о себе скрытым полем auth_form, поэтому ошибки всегда видны на нужной
    // вкладке — независимо от того, с какой страницы пришёл POST.
    $activeMode = old('auth_form', $mode);
    $isLogin = $activeMode !== 'register';
    $loginUrl = route('login', $intended ? ['redirect' => $intended] : []);
    $registerUrl = route('register', $intended ? ['redirect' => $intended] : []);
    $redirectValue = old('redirect', $intended);
@endphp

<div class="card auth-card" data-reveal data-auth>
    <div class="auth-tabs" role="tablist" aria-label="Вход и регистрация">
        <a id="auth-tab-login"
           class="auth-tab @if ($isLogin) is-active @endif"
           href="{{ $loginUrl }}"
           role="tab"
           data-auth-tab="login"
           aria-controls="auth-panel-login"
           aria-selected="{{ $isLogin ? 'true' : 'false' }}"
           tabindex="{{ $isLogin ? '0' : '-1' }}">Войти</a>
        <a id="auth-tab-register"
           class="auth-tab @if (! $isLogin) is-active @endif"
           href="{{ $registerUrl }}"
           role="tab"
           data-auth-tab="register"
           aria-controls="auth-panel-register"
           aria-selected="{{ $isLogin ? 'false' : 'true' }}"
           tabindex="{{ $isLogin ? '-1' : '0' }}">Создать аккаунт</a>
    </div>

    <div id="auth-panel-login"
         class="auth-panel"
         role="tabpanel"
         aria-labelledby="auth-tab-login"
         data-auth-panel="login"
         @if (! $isLogin) hidden @endif>
        <h3 class="center" style="margin-bottom:4px">Личный кабинет</h3>
        <p class="muted center">Записи, курсы и заказы в одном месте.</p>

        <form action="{{ route('login.store') }}" method="post">
            @csrf
            <input type="hidden" name="auth_form" value="login">
            @if ($redirectValue)
                <input type="hidden" name="redirect" value="{{ $redirectValue }}">
            @endif
            <div class="field">
                <label for="login">Телефон или электронная почта</label>
                <input id="login" class="input @error('login') is-invalid @enderror" type="text" name="login" value="{{ old('login') }}" autocomplete="username" required @if ($isLogin) autofocus @endif>
                @error('login')<div class="error-text">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="password">Пароль</label>
                <div class="password-field">
                    <input id="password" class="input @if ($isLogin && $errors->has('password')) is-invalid @endif" type="password" name="password" autocomplete="current-password" required>
                    <button type="button" class="password-toggle" data-password-toggle aria-label="Показать пароль" aria-pressed="false">
                        <x-ico name="eye" class="password-toggle__show" />
                        <x-ico name="eye-off" class="password-toggle__hide" />
                    </button>
                </div>
                @if ($isLogin)
                    @error('password')<div class="error-text">{{ $message }}</div>@enderror
                @endif
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px">
                <label class="checkbox" style="margin:0"><input type="checkbox" name="remember" value="1"><span>Запомнить меня</span></label>
            </div>
            <button class="btn btn--block" type="submit">Войти</button>
        </form>

        <p class="center muted" style="margin:18px 0">или</p>
        <a class="btn btn--ghost btn--block" href="{{ $registerUrl }}" data-auth-tab="register">Создать аккаунт</a>

        <p class="form-note" style="margin-top:16px">Аккаунт оформляет взрослый. Для записи ребёнка отдельный аккаунт ребёнка не требуется.</p>
        <p class="muted" style="font-size:13px;margin-top:10px">Демо-доступ: <strong>demo@korni.local</strong> / <strong>password</strong>.</p>
    </div>

    <div id="auth-panel-register"
         class="auth-panel"
         role="tabpanel"
         aria-labelledby="auth-tab-register"
         data-auth-panel="register"
         @if ($isLogin) hidden @endif>
        <h3 class="center" style="margin-bottom:4px">Создать аккаунт</h3>
        <p class="muted center">Регистрация по электронной почте и паролю.</p>

        <form action="{{ route('register.store') }}" method="post">
            @csrf
            <input type="hidden" name="auth_form" value="register">
            @if ($redirectValue)
                <input type="hidden" name="redirect" value="{{ $redirectValue }}">
            @endif
            <div class="field">
                <label for="name">Имя</label>
                <input id="name" class="input @error('name') is-invalid @enderror" type="text" name="name" value="{{ old('name') }}" required @if (! $isLogin) autofocus @endif>
                @error('name')<div class="error-text">{{ $message }}</div>@enderror
            </div>
            <div class="grid grid-2" style="gap:14px">
                <div class="field">
                    <label for="email">Электронная почта</label>
                    <input id="email" class="input @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" required>
                    @error('email')<div class="error-text">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="phone">Телефон</label>
                    <input id="phone" class="input" type="text" name="phone" value="{{ old('phone') }}">
                </div>
            </div>
            <div class="grid grid-2" style="gap:14px">
                <div class="field">
                    <label for="register-password">Пароль</label>
                    <div class="password-field">
                        <input id="register-password" class="input @if (! $isLogin && $errors->has('password')) is-invalid @endif" type="password" name="password" autocomplete="new-password" minlength="8" required>
                        <button type="button" class="password-toggle" data-password-toggle aria-label="Показать пароль" aria-pressed="false">
                            <x-ico name="eye" class="password-toggle__show" />
                            <x-ico name="eye-off" class="password-toggle__hide" />
                        </button>
                    </div>
                    @if (! $isLogin)
                        @error('password')<div class="error-text">{{ $message }}</div>@enderror
                    @endif
                </div>
                <div class="field">
                    <label for="register-password_confirmation">Повторите пароль</label>
                    <div class="password-field">
                        <input id="register-password_confirmation" class="input" type="password" name="password_confirmation" autocomplete="new-password" required>
                        <button type="button" class="password-toggle" data-password-toggle aria-label="Показать пароль" aria-pressed="false">
                            <x-ico name="eye" class="password-toggle__show" />
                            <x-ico name="eye-off" class="password-toggle__hide" />
                        </button>
                    </div>
                </div>
            </div>
            <div class="field">
                <label class="checkbox">
                    <input type="checkbox" name="consent_data" value="1" required>
                    <span>
                        {{ legal('consent_registration', 'lead', 'Даю согласие на обработку персональных данных для регистрации и работы личного кабинета') }}.
                        <a href="{{ route('legal.show', legal('consent_personal_data', 'slug', 'soglasie-na-obrabotku-dannyh')) }}" target="_blank" rel="noopener">Условия обработки</a>
                    </span>
                </label>
                @error('consent_data')<div class="error-text">{{ $message }}</div>@enderror
                <label class="checkbox" style="margin-top:8px">
                    <input type="checkbox" name="consent_marketing" value="1">
                    <span>{{ legal('consent_marketing', 'lead', 'Согласен(на) получать новости и предложения студии (необязательно)') }}</span>
                </label>
            </div>
            <button class="btn btn--block" type="submit">Зарегистрироваться</button>
            <p class="form-note" style="margin-top:14px">Аккаунт оформляет совершеннолетнее лицо (родитель или законный представитель). Данные участников-несовершеннолетних обрабатываются исключительно в объёме, необходимом для оказания услуги, на основании согласия законного представителя.</p>
        </form>

        <p class="center muted" style="margin:18px 0">Уже есть аккаунт?</p>
        <a class="btn btn--ghost btn--block" href="{{ $loginUrl }}" data-auth-tab="login">Войти</a>
    </div>
</div>
