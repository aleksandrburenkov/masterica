@extends('layouts.site')

@section('title', 'Профиль — '.setting('studio.name', 'Корни'))
@section('robots', 'noindex,nofollow')

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => 'Личный кабинет', 'url' => route('account.dashboard')], ['label' => 'Профиль']]" />
@endsection

@section('content')
    <section class="section section--tight">
        <div class="container">
            <div class="account">
                @include('partials.account-nav')

                <div>
                    <h2>Профиль</h2>
                    <p class="muted">Контактные данные взрослого пользователя.</p>

                    <form class="card" action="{{ route('account.profile.update') }}" method="post" style="max-width:640px">
                        @csrf
                        @method('PATCH')
                        <div class="field">
                            <label for="p-name">Имя</label>
                            <input id="p-name" class="input @error('name') is-invalid @enderror" type="text" name="name" value="{{ old('name', $user->name) }}" required>
                            @error('name')<div class="error-text">{{ $message }}</div>@enderror
                        </div>
                        <div class="field">
                            <label for="p-phone">Телефон</label>
                            <input id="p-phone" class="input" type="text" name="phone" value="{{ old('phone', $user->phone) }}">
                        </div>
                        <div class="field">
                            <label for="p-email">Электронная почта</label>
                            <input id="p-email" class="input @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                            @error('email')<div class="error-text">{{ $message }}</div>@enderror
                        </div>
                        <label class="checkbox" style="margin-bottom:14px">
                            <input type="checkbox" name="consent_marketing" value="1" @checked(old('consent_marketing', $user->consent_marketing))>
                            <span>{{ legal('consent_marketing', 'lead', 'Согласен(на) получать новости и предложения студии (необязательно)') }}</span>
                        </label>
                        <button class="btn" type="submit">Сохранить</button>
                    </form>

                    <div class="card" style="max-width:640px;margin-top:20px">
                        <h3>Согласия и данные</h3>
                        <p class="muted" style="font-size:15px">Мы собираем только минимум, необходимый для занятий, заказов и доступа к курсам. Аккаунт оформляет совершеннолетнее лицо; отдельный аккаунт ребёнка не создаётся.</p>
                        <div class="note-strip">
                            Данные обрабатываются в соответствии с
                            <a href="{{ route('legal.show', legal('privacy_policy', 'slug', 'politika-konfidencialnosti')) }}" target="_blank" rel="noopener">Политикой конфиденциальности</a>
                            и <a href="{{ route('legal.show', legal('consent_personal_data', 'slug', 'soglasie-na-obrabotku-dannyh')) }}" target="_blank" rel="noopener">Согласием на обработку персональных данных</a>.
                            Данные участников-несовершеннолетних обрабатываются исключительно в объёме, необходимом для оказания услуги, на основании согласия законного представителя.
                            Условия работы платформы — в <a href="{{ route('legal.show', legal('platform_terms', 'slug', 'polzovatelskoe-soglashenie')) }}" target="_blank" rel="noopener">Пользовательском соглашении</a>.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
