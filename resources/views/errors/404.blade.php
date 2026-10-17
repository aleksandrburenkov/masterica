@extends('layouts.site')

@section('title', 'Страница не найдена — '.setting('studio.name', 'Корни'))
@section('robots', 'noindex,follow')

@section('content')
    <section class="section">
        <div class="container center" style="max-width:720px">
            <div class="card" data-reveal>
                <div style="font-family:var(--font-display);font-weight:900;font-size:clamp(64px,12vw,140px);line-height:1;background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent">404</div>
                <h1>Страница не найдена</h1>
                <p class="muted">Возможно, адрес изменился или страница была перемещена. Попробуйте начать с каталога.</p>
                <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:16px">
                    <a class="btn" href="{{ route('home') }}">На главную</a>
                    <a class="btn btn--ghost" href="{{ route('masterclasses.index') }}">Мастер-классы</a>
                </div>
            </div>
        </div>
    </section>
@endsection
