@extends('layouts.site')

@section('title', 'Мастер-классы в '.setting('studio.city', 'Брянске').' — каталог занятий')
@section('meta_description', 'Каталог мастер-классов: бумажная лоза, бисер, синельная проволока, фоамиран, макраме, канзаши. Даты, ведущие, возраст и стоимость.')
@section('canonical', route('masterclasses.index'))
@section('robots', $hasFilters ? 'noindex,follow' : 'index,follow')

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => 'Мастер-классы']]" />
@endsection

@section('content')
    <section class="hero hero--video">
        @include('partials.hero-video')
        <div class="container">
            <div class="hero__inner">
                <div class="hero__content" data-reveal>
                    <h1>Мастер-классы в {{ setting('studio.city', 'Брянске') }}</h1>
                    <p class="lead">Творческие занятия для детей и взрослых — в студии и на выезде.</p>
                    <a class="btn" href="#catalog">Выбрать направление</a>
                </div>
            </div>
        </div>
    </section>

    <section class="section" id="catalog" style="scroll-margin-top:140px">
        <div class="container">
            <div class="section-head">
                <h2>Найдите своё направление</h2>
                <p>Фильтры помогают выбрать категорию. Для поисковых систем индексируются только базовые страницы каталога.</p>
            </div>

            <div class="filters">
                <a class="filter-pill {{ !request('technique') ? 'is-active' : '' }}" href="{{ route('masterclasses.index') }}">Все направления</a>
                @foreach ($techniques as $technique)
                    <a class="filter-pill {{ request('technique') === $technique->slug ? 'is-active' : '' }}" href="{{ route('masterclasses.index', ['technique' => $technique->slug]) }}">{{ $technique->name }}</a>
                @endforeach
            </div>

            <form class="filter-bar" method="get" action="{{ route('masterclasses.index') }}">
                @if (request('technique'))
                    <input type="hidden" name="technique" value="{{ request('technique') }}">
                @endif
                <div class="field">
                    <label for="f-age">Возраст участника</label>
                    <input id="f-age" class="input" type="number" name="age" min="3" max="99" value="{{ request('age') }}" placeholder="Например, 12">
                </div>
                <div class="field">
                    <label for="f-level">Уровень</label>
                    <select id="f-level" class="select" name="level">
                        <option value="">Любой уровень</option>
                        <option value="beginner" @selected(request('level') === 'beginner')>Начинающий</option>
                        <option value="advanced" @selected(request('level') === 'advanced')>Продолжающий</option>
                    </select>
                </div>
                <div class="field">
                    <label for="f-format">Формат</label>
                    <select id="f-format" class="select" name="format">
                        <option value="">Любой формат</option>
                        <option value="studio" @selected(request('format') === 'studio')>В студии</option>
                        <option value="outreach" @selected(request('format') === 'outreach')>Выездной</option>
                    </select>
                </div>
                <div class="field" style="display:flex;align-items:flex-end;gap:10px">
                    <button class="btn btn--sm" type="submit">Применить</button>
                    @if ($hasFilters)
                        <a class="btn btn--ghost btn--sm" href="{{ route('masterclasses.index') }}">Сбросить</a>
                    @endif
                </div>
            </form>

            @if ($masterclasses->isEmpty())
                <div class="empty">
                    <x-ico name="sparkles" />
                    <h3>По вашим условиям ничего не найдено</h3>
                    <p>Попробуйте изменить фильтры или сбросить их — или свяжитесь со студией, мы подберём занятие.</p>
                    <a class="btn btn--ghost" href="{{ route('masterclasses.index') }}">Сбросить фильтры</a>
                </div>
            @else
                <div class="grid grid-3">
                    @foreach ($masterclasses as $masterclass)
                        @include('partials.masterclass-card', ['masterclass' => $masterclass])
                    @endforeach
                </div>

                <div style="margin-top:36px">
                    {{ $masterclasses->links() }}
                </div>
            @endif
        </div>
    </section>

    <section class="section" style="background:rgba(255,255,255,.5)">
        <div class="container">
            <div class="section-head">
                <h2>Как выбрать занятие</h2>
            </div>
            <div style="max-width:900px;margin:0 auto">
                <x-faq :items="[
                    ['q' => 'Можно ли посмотреть дату до записи?', 'a' => 'Да. Дата и время должны быть опубликованы в карточке выбранного сеанса на странице мастер-класса.'],
                    ['q' => 'Что будет на занятии?', 'a' => 'Описание программы и материалов размещено на отдельной странице мастер-класса.'],
                    ['q' => 'Кому подходит?', 'a' => 'Возрастной диапазон указывается для конкретного сеанса; заявку на ребёнка оформляет взрослый.'],
                ]" />
            </div>
        </div>
    </section>

    @include('partials.outreach-cta')
@endsection
