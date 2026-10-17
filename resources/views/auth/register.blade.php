@extends('layouts.site')

@section('title', 'Создание аккаунта — '.setting('studio.name', 'Корни'))
@section('robots', 'noindex,nofollow')

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => 'Личный кабинет', 'url' => route('login')], ['label' => 'Регистрация']]" />
@endsection

@section('content')
    <section class="section section--tight">
        <div class="container">
            <div class="section-head"><h2>Создайте аккаунт</h2><p>Регистрация по электронной почте и паролю.</p></div>
            <div class="auth-wrap">
                <div class="card auth-card" data-reveal>
                    <div class="auth-tabs">
                        <a href="{{ route('login') }}">Войти</a>
                        <a class="is-active" href="{{ route('register') }}">Создать аккаунт</a>
                    </div>

                    <form action="{{ route('register.store') }}" method="post" novalidate>
                        @csrf
                        <div class="field">
                            <label for="name">Имя</label>
                            <input id="name" class="input @error('name') is-invalid @enderror" type="text" name="name" value="{{ old('name') }}" required autofocus>
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
                                <label for="password">Пароль</label>
                                <div class="password-field">
                                    <input id="password" class="input @error('password') is-invalid @enderror" type="password" name="password" autocomplete="new-password" required>
                                    <button type="button" class="password-toggle" data-password-toggle aria-label="Показать пароль" aria-pressed="false">
                                        <x-ico name="eye" class="password-toggle__show" />
                                        <x-ico name="eye-off" class="password-toggle__hide" />
                                    </button>
                                </div>
                                @error('password')<div class="error-text">{{ $message }}</div>@enderror
                            </div>
                            <div class="field">
                                <label for="password_confirmation">Повторите пароль</label>
                                <div class="password-field">
                                    <input id="password_confirmation" class="input" type="password" name="password_confirmation" autocomplete="new-password" required>
                                    <button type="button" class="password-toggle" data-password-toggle aria-label="Показать пароль" aria-pressed="false">
                                        <x-ico name="eye" class="password-toggle__show" />
                                        <x-ico name="eye-off" class="password-toggle__hide" />
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="field">
                            <label class="checkbox"><input type="checkbox" name="consent_data" value="1" required><span>Согласен(на) на обработку данных</span></label>
                            @error('consent_data')<div class="error-text">{{ $message }}</div>@enderror
                        </div>
                        <button class="btn btn--block" type="submit">Зарегистрироваться</button>
                        <p class="form-note" style="margin-top:14px">Аккаунт оформляет взрослый (родитель или представитель). Подтверждение email в локальной среде не блокирует работу.</p>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
