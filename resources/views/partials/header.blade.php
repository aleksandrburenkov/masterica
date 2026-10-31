@php
    $navItems = [
        ['label' => 'О нас', 'route' => 'about'],
        ['label' => 'Как проходят занятия', 'route' => 'how-classes-work'],
        ['label' => 'Мастер-классы', 'route' => 'masterclasses.index'],
        ['label' => 'Курсы', 'route' => 'courses.index'],
        ['label' => 'Магазин', 'route' => 'shop.index'],
        ['label' => 'Отзывы', 'route' => 'reviews.index'],
        ['label' => 'Контакты', 'route' => 'contacts'],
    ];
@endphp
<header class="site-header" data-header>
    <div class="container">
        <div class="header-top">
            <a class="brand" href="{{ route('home') }}" aria-label="{{ setting('studio.name', 'Корни') }} — на главную">
                <img src="{{ asset('images/logo.png') }}"
                    alt="Логотип арт-студии «{{ setting('studio.name', 'Корни') }}»">
                {{-- <span class="brand__text">
                    <span class="brand__studio">Арт-студия</span>
                    <span class="brand__name">{{ setting('studio.name', 'Корни') }}</span>
                </span> --}}
            </a>

            <div class="header-actions">
                <a class="header-phone" href="tel:{{ preg_replace('/[^0-9+]/', '', setting('studio.phone', '')) }}">
                    {{ setting('studio.phone', '+7 (906) 698-31-06') }}
                </a>
                <a class="btn btn--sm" href="{{ route('masterclasses.index') }}#catalog">Записаться на занятие</a>
            </div>
        </div>

        <div class="header-nav">
            <nav class="nav-links" aria-label="Основная навигация">
                @foreach ($navItems as $item)
                    @if (Route::has($item['route']))
                        <a href="{{ route($item['route']) }}" @class(['is-active' => request()->routeIs($item['route'] . '*')])>{{ $item['label'] }}</a>
                    @endif
                @endforeach
            </nav>

            <div class="header-user">
                <div class="search-wrap" data-search>
                    <form class="search-form" action="{{ route('search') }}" method="get" role="search">
                        <label class="sr-only" for="site-search">Поиск по сайту</label>
                        <input id="site-search" class="input" type="search" name="q"
                            value="{{ request('q') }}" placeholder="Занятия, курсы, товары…">
                        <button type="submit" aria-label="Искать"><x-ico name="search" /></button>
                    </form>
                    <button type="button" class="icon-btn search-toggle" data-search-toggle aria-expanded="false"
                        aria-label="Открыть поиск">
                        <span class="search-toggle__search"><x-ico name="search" /></span>
                        <span class="search-toggle__close"><x-ico name="close" /></span>
                    </button>
                </div>

                @auth
                    <a class="account-link" href="{{ route('account.dashboard') }}" title="Личный кабинет">
                        <x-ico name="user" />
                        <span>{{ auth()->user()->name }}</span>
                    </a>
                @else
                    <a class="account-link" href="{{ route('login') }}">
                        <x-ico name="user" />
                        <span>Личный кабинет</span>
                    </a>
                @endauth

                @include('partials.cart-widget')

                <button type="button" class="burger" data-burger aria-label="Меню" aria-expanded="false">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>

        <nav class="mobile-menu" aria-label="Мобильная навигация">
            @foreach ($navItems as $item)
                @if (Route::has($item['route']))
                    <a href="{{ route($item['route']) }}">{{ $item['label'] }}</a>
                @endif
            @endforeach
            @auth
                <a href="{{ route('account.dashboard') }}">Личный кабинет</a>
                <a href="{{ route('account.profile') }}">Профиль</a>
            @else
                <a href="{{ route('login') }}">Войти</a>
                <a href="{{ route('register') }}">Создать аккаунт</a>
            @endauth
        </nav>
    </div>
</header>
