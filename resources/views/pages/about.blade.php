@extends('layouts.site')

@section('title', 'О студии «'.setting('studio.name', 'Корни').'» — мастера и направление')
@section('meta_description', 'Арт-студия «Корни» в '.setting('studio.city', 'Брянске').'. Ведущий мастер — Ирина Буренкова. Плетение из бумажной лозы и другие техники.')
@section('canonical', route('about'))

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => 'О студии']]" />
@endsection

@section('content')
    <section class="hero hero--video">
        @include('partials.hero-video')
        <div class="container">
            <div class="hero__inner">
                <div class="hero__content" data-reveal>
                    <h1>О студии «{{ setting('studio.name', 'Корни') }}»</h1>
                    <p class="lead">Творческая студия в {{ setting('studio.city', 'Брянске') }}: занятия для детей и взрослых и авторские работы ручной работы. Основное направление — плетение из бумажной лозы.</p>
                    <a class="btn" href="{{ route('masterclasses.index') }}">Выбрать занятие</a>
                </div>
            </div>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container">
            <div class="grid grid-3">
                <div class="card feature" data-reveal><span class="feature__icon"><x-ico name="heart" /></span><div><h4>Тёплая атмосфера</h4><p>Небольшие группы и внимание к каждому участнику.</p></div></div>
                <div class="card feature" data-reveal><span class="feature__icon"><x-ico name="palette" /></span><div><h4>Разные техники</h4><p>Бумажная лоза, канзаши, макраме, фоамиран и другие направления.</p></div></div>
                <div class="card feature" data-reveal><span class="feature__icon"><x-ico name="shield" /></span><div><h4>Материалы включены</h4><p>Для обычного занятия материалы предоставляет студия.</p></div></div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-head"><h2>Мастера студии</h2><p>Профили других мастеров можно добавить по мере роста команды.</p></div>
            <div class="grid grid-2">
                @foreach ($masters as $master)
                    <div class="card master-card" data-reveal>
                        <img src="{{ asset($master->photo ?? 'images/photo-1.jpg') }}" alt="{{ $master->name }}">
                        <div>
                            <h3 style="margin-bottom:6px">{{ $master->name }}</h3>
                            <p class="lead-muted" style="margin-bottom:8px">{{ $master->title }}</p>
                            <p class="muted" style="margin:0;font-size:15px">{{ $master->bio }}</p>
                            @if ($master->techniques->isNotEmpty())
                                <div class="pill-list" style="margin-top:12px">
                                    @foreach ($master->techniques as $technique)
                                        <span class="chip">{{ $technique->name }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
                <div class="card" data-reveal>
                    <h3>Команда студии</h3>
                    <p class="muted">Ирина — владелец и первый мастер. Будущие мастера — сотрудники той же студии с назначаемыми занятиями. Отдельные магазины мастеров и выплаты не используются.</p>
                    <a class="btn btn--ghost" href="{{ route('contacts') }}">Связаться со студией</a>
                </div>
            </div>
        </div>
    </section>

    <section class="section" style="background:rgba(255,255,255,.5)">
        <div class="container">
            <div class="section-head"><h2>Как всё устроено</h2></div>
            <div style="max-width:900px;margin:0 auto">
                <x-faq :items="[
                    ['q' => 'Кто может приходить на занятия?', 'a' => 'Дети и взрослые. Для каждого сеанса указаны возрастные условия, а заявку оформляет взрослый.'],
                    ['q' => 'Где проходят занятия?', 'a' => 'В студии по адресу '.setting('studio.address').'. Также доступны выездные мастер-классы.'],
                    ['q' => 'Как оплатить?', 'a' => 'Оплата в студии. В локальной версии платёжный провайдер не подключён.'],
                ]" />
            </div>
        </div>
    </section>

    @include('partials.outreach-cta')
@endsection
