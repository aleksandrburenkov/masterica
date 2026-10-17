@php
    $items = [
        ['label' => 'Обзор', 'route' => 'account.dashboard', 'num' => '01'],
        ['label' => 'Мои записи', 'route' => 'account.bookings', 'num' => '02'],
        ['label' => 'Мои курсы', 'route' => 'account.courses', 'num' => '03'],
        ['label' => 'Мои заказы', 'route' => 'account.orders', 'num' => '04'],
        ['label' => 'Профиль', 'route' => 'account.profile', 'num' => '05'],
    ];
@endphp
<aside class="account-nav">
    <h4>Личный кабинет</h4>
    <ul>
        @foreach ($items as $item)
            <li>
                <a href="{{ route($item['route']) }}" @class(['is-active' => request()->routeIs($item['route'])])>
                    <span class="num">{{ $item['num'] }}</span>
                    <span>{{ $item['label'] }}</span>
                </a>
            </li>
        @endforeach
        <li>
            <form action="{{ route('logout') }}" method="post">
                @csrf
                <button class="btn btn--ghost btn--sm btn--block" type="submit" style="margin-top:8px">Выйти</button>
            </form>
        </li>
    </ul>
</aside>
