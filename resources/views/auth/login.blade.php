@extends('layouts.site')

@section('title', 'Вход в личный кабинет — '.setting('studio.name', 'Корни'))
@section('robots', 'noindex,nofollow')

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => 'Личный кабинет', 'url' => route('login')], ['label' => 'Вход']]" />
@endsection

@section('content')
    <section class="section section--tight">
        <div class="container">
            <div class="auth-layout">
                <div class="auth-intro" data-reveal>
                    <h1>Вход в личный кабинет</h1>
                    <p class="lead">Продолжите пользоваться записями, заказами и доступом к курсам.</p>
                    <h2 class="auth-intro__sub">Один профиль для всех направлений</h2>
                    <p class="muted">Записи, курсы и заказы — в одном месте. Войдите или создайте аккаунт, чтобы продолжить.</p>
                </div>

                @include('auth._card', ['mode' => 'login', 'intended' => $intended])
            </div>
        </div>
    </section>
@endsection
