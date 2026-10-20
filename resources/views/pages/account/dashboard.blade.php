@extends('layouts.site')

@section('title', 'Личный кабинет — '.setting('studio.name', 'Корни'))
@section('robots', 'noindex,nofollow')

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => 'Личный кабинет']]" />
@endsection

@section('content')
    <section class="section section--tight">
        <div class="container">
            <div class="section-head" style="text-align:left;margin-left:0">
                <h1>Личный кабинет</h1>
                <p>Все ваши записи, покупки и курсы — в едином профиле.</p>
            </div>

            <div class="account">
                @include('partials.account-nav')

                <div>
                    <span class="chip">Мой аккаунт</span>
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-top:12px">
                        <div>
                            <h2 style="margin:0">Здравствуйте, {{ $user->name }}!</h2>
                            <p class="muted">Здесь собраны все ваши записи и покупки.</p>
                        </div>
                        <span class="avatar" style="width:56px;height:56px;font-size:22px">{{ mb_substr($user->name, 0, 1) }}</span>
                    </div>

                    <div class="tiles" style="margin-top:20px">
                        <a class="tile" href="{{ route('account.bookings') }}"><div class="tile__label">Мои записи</div><div class="tile__value">{{ $counts['bookings'] }}</div><div class="muted" style="font-size:13px">Предстоящие и прошедшие</div></a>
                        <a class="tile" href="{{ route('account.courses') }}"><div class="tile__label">Мои курсы</div><div class="tile__value">{{ $counts['courses'] }}</div><div class="muted" style="font-size:13px">Доступ и прогресс</div></a>
                        <a class="tile" href="{{ route('account.orders') }}"><div class="tile__label">Мои заказы</div><div class="tile__value">{{ $counts['orders'] }}</div><div class="muted" style="font-size:13px">История покупок</div></a>
                        <a class="tile" href="{{ route('masterclasses.index') }}"><div class="tile__label">Записаться</div><div class="tile__value">＋</div><div class="muted" style="font-size:13px">Выбрать занятие</div></a>
                    </div>

                    <div class="grid grid-2" style="margin-top:24px">
                        <div class="card">
                            <h3>Ближайшее занятие</h3>
                            @if ($upcoming && $upcoming->session)
                                <div class="fact" style="background:var(--bg-2);border-radius:var(--radius-sm);padding:16px">
                                    <div class="fact__value">{{ $upcoming->session->masterclass->title }}</div>
                                    <div class="muted">{{ $upcoming->session->starts_at->copy()->setTimezone($upcoming->session->timezone)->translatedFormat('d F, l, H:i') }}</div>
                                    <div class="meta-row" style="margin-top:6px">
                                        <span class="badge badge--green">{{ $upcoming->status->label() }}</span>
                                        <span class="muted">{{ $upcoming->participants_count }} чел. · {{ money($upcoming->total_cents) }}</span>
                                    </div>
                                </div>
                            @else
                                <p class="muted">Когда появится запись, она отобразится здесь.</p>
                                <a class="btn btn--ghost btn--sm" href="{{ route('masterclasses.index') }}">Выбрать занятие</a>
                            @endif
                        </div>

                        <div class="card">
                            <h3>Продолжить курс</h3>
                            @if ($activeEnrollment)
                                <div class="fact__value">{{ $activeEnrollment->course->title }}</div>
                                <div class="progress-bar" style="margin:14px 0 8px"><span style="width:{{ $progress }}%"></span></div>
                                <div class="muted" style="font-size:13px;margin-bottom:14px">Прогресс: {{ $progress }}%</div>
                                @if ($activeLesson)
                                    <a class="btn btn--sm" href="{{ route('courses.learn', [$activeEnrollment->course->slug, $activeLesson->id]) }}">Открыть курс</a>
                                @else
                                    <p class="muted" style="font-size:13px">Материалы курса ещё не опубликованы.</p>
                                    <a class="btn btn--ghost btn--sm" href="{{ route('courses.show', $activeEnrollment->course->slug) }}">О курсе</a>
                                @endif
                            @else
                                <p class="muted">Купленные курсы появятся в этом блоке с прогрессом и ссылкой на следующий урок.</p>
                                <a class="btn btn--ghost btn--sm" href="{{ route('courses.index') }}">Смотреть курсы</a>
                            @endif
                        </div>
                    </div>

                    @if ($notifications->isNotEmpty())
                        <div class="card" style="margin-top:24px">
                            <h3>Уведомления</h3>
                            <div class="stack">
                                @foreach ($notifications as $notification)
                                    <div style="display:flex;justify-content:space-between;gap:12px;border-bottom:1px solid var(--line);padding-bottom:10px">
                                        <div>
                                            <div style="font-weight:700;color:var(--ink)">{{ $notification->title }}</div>
                                            <div class="muted" style="font-size:14px">{{ $notification->body }}</div>
                                        </div>
                                        <span class="muted" style="font-size:13px;white-space:nowrap">{{ $notification->created_at->diffForHumans() }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="note-strip" style="margin-top:24px">Кабинет закрыт от индексации. Для событий и покупок показывается реальное состояние, а не макетные показатели.</div>
                </div>
            </div>
        </div>
    </section>
@endsection
