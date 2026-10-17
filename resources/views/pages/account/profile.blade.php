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

                    <form class="card" action="{{ route('account.profile.update') }}" method="post" novalidate style="max-width:640px">
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
                        <button class="btn" type="submit">Сохранить</button>
                    </form>

                    <div class="card" style="max-width:640px;margin-top:20px">
                        <h3>Согласия и данные</h3>
                        <p class="muted" style="font-size:15px">Мы собираем только минимум, необходимый для занятий, заказов и доступа к курсам. Аккаунт оформляет взрослый; отдельный аккаунт ребёнка не создаётся.</p>
                        <div class="note-strip">{{ setting('studio.legal_note') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
