@props(['session'])

@php
    $local = $session->starts_at->copy()->setTimezone($session->timezone);
    $left = $session->seatsLeft();
@endphp

<div class="session-item">
    <div>
        <div class="title">{{ $local->translatedFormat('d F, l') }}, {{ $local->format('H:i') }}</div>
        <div class="meta">
            <span>{{ $session->masterclass->title }}</span>
            @if ($session->master)<span>Ведущий: {{ $session->master->name }}</span>@endif
            <span>{{ $session->location_name ?? setting('studio.address') }}</span>
            <span>{{ $session->timezone }}</span>
        </div>
    </div>
    <div style="text-align:right">
        <div class="price" style="font-size:18px">{{ money($session->priceCents()) }}</div>
        @if ($left > 0)
            <span class="badge badge--green">Свободно: {{ $left }}</span>
        @else
            <span class="badge badge--red">Мест нет</span>
        @endif
    </div>
</div>
