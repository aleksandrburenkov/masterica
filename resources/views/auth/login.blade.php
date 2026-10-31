@extends('layouts.site')

@section('title', 'Вход в личный кабинет — '.setting('studio.name', 'Корни'))
@section('robots', 'noindex,nofollow')

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => 'Личный кабинет', 'url' => route('login')], ['label' => 'Вход']]" />
@endsection

@section('content')
    <section class="hero">
        <div class="container">
            <div class="hero__inner">
                <div class="hero__content" data-reveal>
                    <h1>Вход в личный кабинет</h1>
                    <p class="lead">Продолжите пользоваться записями, заказами и доступом к курсам.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container">
            <div class="section-head"><h2>Один профиль для всех направлений</h2></div>
            <div class="auth-wrap">
                <div class="card auth-card" data-reveal>
                    <div class="auth-tabs">
                        <a class="is-active" href="{{ route('login') }}">Войти</a>
                        <a href="{{ route('register', $intended ? ['redirect' => $intended] : []) }}">Создать аккаунт</a>
                    </div>

                    <h3 class="center" style="margin-bottom:4px">Личный кабинет</h3>
                    <p class="muted center">Записи, курсы и заказы в одном месте.</p>

                    <form action="{{ route('login.store') }}" method="post">
                        @csrf
                        @if ($intended)
                            <input type="hidden" name="redirect" value="{{ $intended }}">
                        @endif
                        <div class="field">
                            <label for="login">Телефон или электронная почта</label>
                            <input id="login" class="input @error('login') is-invalid @enderror" type="text" name="login" value="{{ old('login') }}" autocomplete="username" required autofocus>
                            @error('login')<div class="error-text">{{ $message }}</div>@enderror
                        </div>
                        <div class="field">
                            <label for="password">Пароль</label>
                            <div class="password-field">
                                <input id="password" class="input @error('password') is-invalid @enderror" type="password" name="password" autocomplete="current-password" required>
                                <button type="button" class="password-toggle" data-password-toggle aria-label="Показать пароль" aria-pressed="false">
                                    <x-ico name="eye" class="password-toggle__show" />
                                    <x-ico name="eye-off" class="password-toggle__hide" />
                                </button>
                            </div>
                            @error('password')<div class="error-text">{{ $message }}</div>@enderror
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px">
                            <label class="checkbox" style="margin:0"><input type="checkbox" name="remember" value="1"><span>Запомнить меня</span></label>
                        </div>
                        <button class="btn btn--block" type="submit">Войти</button>
                    </form>

                    <p class="center muted" style="margin:18px 0">или</p>
                    <a class="btn btn--ghost btn--block" href="{{ route('register', $intended ? ['redirect' => $intended] : []) }}">Создать аккаунт</a>

                    <p class="form-note" style="margin-top:16px">Аккаунт оформляет взрослый. Для записи ребёнка отдельный аккаунт ребёнка не требуется.</p>
                    <p class="muted" style="font-size:13px;margin-top:10px">Демо-доступ: <strong>demo@korni.local</strong> / <strong>password</strong>.</p>
                </div>
            </div>
        </div>
    </section>
@endsection
