@extends('layouts.site')

@section('title', 'Как проходят занятия — '.setting('studio.name', 'Корни'))
@section('meta_description', 'Как устроены занятия: выбор техники, подготовка, ход занятия и результат. Материалы и длительность указаны в карточке сеанса.')
@section('canonical', route('how-classes-work'))

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => 'Как проходят занятия']]" />
@endsection

@section('content')
    <section class="hero hero--video">
        @include('partials.hero-video')
        <div class="container">
            <div class="hero__inner">
                <div class="hero__content" data-reveal>
                    <h1>Как проходят занятия</h1>
                    <p class="lead">От выбора техники до готовой работы: понятные шаги, материалы от студии и поддержка ведущего.</p>
                    <a class="btn" href="{{ route('masterclasses.index') }}">Выбрать мастер-класс</a>
                </div>
            </div>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container">
            <div class="steps">
                <div class="step" data-reveal><div class="step__num">Шаг 1</div><h4>Выбор техники и формата</h4><p class="muted" style="margin:0">Посмотрите каталог: техника, возраст, длительность и место указаны в карточке.</p></div>
                <div class="step" data-reveal><div class="step__num">Шаг 2</div><h4>Запись на сеанс</h4><p class="muted" style="margin:0">Выберите дату, проверьте время и условиях, оформите запись.</p></div>
                <div class="step" data-reveal><div class="step__num">Шаг 3</div><h4>Занятие и результат</h4><p class="muted" style="margin:0">Материалы предоставляет студия; готовую работу забираете домой.</p></div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="detail">
                <div data-reveal>
                    <h2>Что важно знать</h2>
                    <ul class="list-check">
                        <li><x-ico name="check-circle" /><span>Обычное занятие длится {{ (int) (setting('pricing.workshop_duration_minutes', 120) / 60) }} часа, вместимость сеанса — {{ setting('pricing.workshop_capacity', 8) }} участников.</span></li>
                        <li><x-ico name="check-circle" /><span>Уровень — начинающий; материалы включены в стоимость.</span></li>
                        <li><x-ico name="check-circle" /><span>Место резервируется на {{ setting('rules.hold_minutes', '15') }} минут во время оформления, затем запись подтверждается.</span></li>
                        <li><x-ico name="check-circle" /><span>{{ setting('rules.cancel_policy') }}</span></li>
                    </ul>
                </div>
                <div class="card" data-reveal>
                    <h3>Частые вопросы</h3>
                    <div class="stack">
                        <div><strong>Нужно ли что-то приносить?</strong><p class="muted" style="margin:0">Для обычного занятия — нет, материалы предоставляет студия.</p></div>
                        <div><strong>Можно ли прийти с ребёнком?</strong><p class="muted" style="margin:0">Да, возрастные условия указаны в карточке сеанса; заявку оформляет взрослый.</p></div>
                        <div><strong>Как отменить запись?</strong><p class="muted" style="margin:0">Из личного кабинета. {{ setting('rules.cancel_policy') }}</p></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('partials.outreach-cta')
@endsection
