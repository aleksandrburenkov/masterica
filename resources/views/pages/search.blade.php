@extends('layouts.site')

@section('title', 'Поиск — '.setting('studio.name', 'Корни'))
@section('robots', 'noindex,follow')

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => 'Поиск']]" />
@endsection

@section('content')
    <section class="section section--tight">
        <div class="container">
            <div class="section-head" style="text-align:left;margin-left:0">
                <h1>Поиск</h1>
                <form method="get" action="{{ route('search') }}" role="search" style="max-width:560px">
                    <div class="field" style="margin:0;display:flex;gap:10px">
                        <input class="input" type="search" name="q" value="{{ $q }}" placeholder="Занятия, курсы, товары…" autofocus>
                        <button class="btn" type="submit">Найти</button>
                    </div>
                </form>
            </div>

            @if (mb_strlen($q) < 2)
                <p class="muted">Введите минимум два символа для поиска.</p>
            @else
                @php $total = $masterclasses->count() + $products->count() + $courses->count() + $guides->count(); @endphp

                @if ($total === 0)
                    <div class="empty"><x-ico name="search" /><h3>Ничего не найдено</h3><p>Попробуйте другой запрос.</p></div>
                @else
                    <p class="muted">Найдено результатов: {{ $total }}.</p>

                    @if ($masterclasses->isNotEmpty())
                        <h2 style="font-size:24px;margin-top:24px">Мастер-классы</h2>
                        <div class="stack">
                            @foreach ($masterclasses as $item)
                                <a class="card search-result" href="{{ route('masterclasses.show', $item->slug) }}"><strong>{{ $item->title }}</strong><div class="muted">{{ $item->short_description }}</div></a>
                            @endforeach
                        </div>
                    @endif

                    @if ($courses->isNotEmpty())
                        <h2 style="font-size:24px;margin-top:24px">Курсы</h2>
                        <div class="stack">
                            @foreach ($courses as $item)
                                <a class="card search-result" href="{{ route('courses.show', $item->slug) }}"><strong>{{ $item->title }}</strong><div class="muted">{{ $item->short_description }}</div></a>
                            @endforeach
                        </div>
                    @endif

                    @if ($products->isNotEmpty())
                        <h2 style="font-size:24px;margin-top:24px">Работы</h2>
                        <div class="stack">
                            @foreach ($products as $item)
                                <a class="card search-result" href="{{ route('shop.show', $item->slug) }}"><strong>{{ $item->title }}</strong><div class="muted">{{ $item->short_description }}</div></a>
                            @endforeach
                        </div>
                    @endif

                    @if ($guides->isNotEmpty())
                        <h2 style="font-size:24px;margin-top:24px">Материалы</h2>
                        <div class="stack">
                            @foreach ($guides as $item)
                                <a class="card search-result" href="{{ route('guides.show', $item->slug) }}"><strong>{{ $item->title }}</strong><div class="muted">{{ $item->excerpt }}</div></a>
                            @endforeach
                        </div>
                    @endif
                @endif
            @endif
        </div>
    </section>
@endsection
