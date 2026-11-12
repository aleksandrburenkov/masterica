@extends('layouts.site')

@section('title', ($heading ?? 'Страница на техническом обслуживании').' — '.setting('studio.name', 'Корни'))
@section('meta_description', setting('pages.maintenance_text'))
@section('robots', 'noindex,follow')

@section('content')
    <section class="section section--tight">
        <div class="container" style="max-width:720px">
            <div class="card" style="text-align:center">
                <span class="chip">Техническое обслуживание</span>
                <h1 style="margin-top:12px">{{ $heading ?? 'Страница временно недоступна' }}</h1>
                <p class="lead-muted">{{ setting('pages.maintenance_text', 'Страница находится на техническом обслуживании.') }}</p>
                <a class="btn" href="{{ route('home') }}">На главную</a>
            </div>
        </div>
    </section>
@endsection
