@extends('layouts.site')

@section('title', 'Мои записи — '.setting('studio.name', 'Корни'))
@section('robots', 'noindex,nofollow')

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => 'Личный кабинет', 'url' => route('account.dashboard')], ['label' => 'Мои записи']]" />
@endsection

@section('content')
    <section class="section section--tight">
        <div class="container">
            <div class="account">
                @include('partials.account-nav')

                <div>
                    <h2>Мои записи</h2>
                    <p class="muted">Предстоящие и прошедшие занятия.</p>

                    @if ($bookings->isEmpty())
                        <div class="empty">
                            <x-ico name="calendar" />
                            <h3>Записей пока нет</h3>
                            <p>Выберите мастер-класс и оформите запись — она появится здесь.</p>
                            <a class="btn" href="{{ route('masterclasses.index') }}">Выбрать занятие</a>
                        </div>
                    @else
                        <div class="stack">
                            @foreach ($bookings as $booking)
                                @php
                                    $session = $booking->session;
                                    $local = $session?->starts_at->copy()->setTimezone($session->timezone);
                                    $canCancel = auth()->user()->can('cancel', $booking);
                                @endphp
                                <div class="card">
                                    <div style="display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap">
                                        <div>
                                            <h3 style="margin-bottom:4px">{{ $session?->masterclass->title ?? 'Мастер-класс' }}</h3>
                                            <div class="meta-row">
                                                @if ($local)<span><x-ico name="calendar" style="width:15px;height:15px;vertical-align:-2px" /> {{ $local->translatedFormat('d F, l, H:i') }}</span>@endif
                                                <span>Запись № {{ $booking->number }}</span>
                                                <span>{{ $booking->participants_count }} чел.</span>
                                                <span>Сумма: {{ money($booking->total_cents) }}</span>
                                            </div>
                                        </div>
                                        <div style="text-align:right">
                                            <span class="badge {{ $booking->status->value === 'confirmed' ? 'badge--green' : ($booking->status->value === 'cancelled' ? 'badge--red' : 'badge--amber') }}">{{ $booking->status->label() }}</span>
                                            <div class="muted" style="font-size:13px;margin-top:6px">Оплата: {{ $booking->payment_status->label() }}</div>
                                        </div>
                                    </div>

                                    @if ($canCancel)
                                        <form action="{{ route('bookings.cancel', $booking->number) }}" method="post" style="margin-top:14px">
                                            @csrf
                                            <input type="hidden" name="cancel_reason" value="Отмена пользователем">
                                            <button class="btn btn--ghost btn--sm" type="submit" onclick="return confirm('Отменить запись?')">Отменить запись</button>
                                        </form>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
