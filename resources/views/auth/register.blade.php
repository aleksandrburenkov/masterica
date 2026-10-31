@extends('layouts.site')

@section('title', 'Создание аккаунта — '.setting('studio.name', 'Корни'))
@section('robots', 'noindex,nofollow')

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => 'Личный кабинет', 'url' => route('login')], ['label' => 'Регистрация']]" />
@endsection

@section('content')
    <section class="section section--tight">
        <div class="container">
            <div class="auth-layout">
                <div class="auth-intro" data-reveal>
                    <h1>Создайте аккаунт</h1>
                    <p class="lead">Продолжите пользоваться записями, заказами и доступом к курсам.</p>
                    <h2 class="auth-intro__sub">Один профиль для всех направлений</h2>
                    <p class="muted">Регистрация по электронной почте и паролю. Записи, курсы и заказы — в одном личном кабинете.</p>
                </div>

                @include('auth._card', ['mode' => 'register', 'intended' => $intended])
            </div>
        </div>
    </section>
@endsection
