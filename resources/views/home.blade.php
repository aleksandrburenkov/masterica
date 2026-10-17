@extends('layouts.site')

@section('title', 'Творческие мастер-классы для детей и взрослых в ' . setting('studio.city', 'Брянске') . ' — ' .
    setting('studio.name', 'Корни'))
@section('meta_description', 'Бумажная лоза, канзаши, макраме, фоамиран и другие техники. Выбирайте занятие в студии или
    на выезде, записывайтесь онлайн.')

    @push('structured')
        <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => setting('studio.name', 'Корни'),
            'description' => setting('studio.og_description'),
            'telephone' => setting('studio.phone'),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => setting('studio.address'),
                'addressLocality' => setting('studio.city'),
                'addressCountry' => 'RU',
            ],
            'openingHours' => setting('studio.hours'),
            'url' => route('home'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
    @endpush

@section('content')
    <section class="hero">
        <div class="container">
            <div class="hero__inner">
                <div class="hero__content" data-reveal>
                    <h1>Творческие мастер-классы для детей и взрослых в {{ setting('studio.city', 'Брянске') }}</h1>
                    <p class="lead">Бумажная лоза, бисероплетение, флористика и другие техники — выбирайте занятие, а
                        детали уточняйте в карточке сеанса.</p>
                    <a class="btn" href="{{ route('masterclasses.index') }}">Выбрать мастер-класс</a>
                </div>
                <div class="hero__media" data-reveal="fade">
                    <img src="{{ asset('images/hero-masterclass.png') }}"
                        alt="Занятие в творческой студии «{{ setting('studio.name', 'Корни') }}»" fetchpriority="high">
                </div>
            </div>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container">
            <div class="section-head">
                <h2>Творчество в своём темпе</h2>
                <p>Арт-студия «{{ setting('studio.name', 'Корни') }}» — занятия и авторские работы в
                    {{ setting('studio.city', 'в Брянске') }}. Для взрослых и детей. Возрастные условия указываются в
                    расписании.</p>
            </div>
            <div class="grid grid-3">
                <div class="card feature" data-reveal>
                    <span class="feature__icon"><x-ico name="users" /></span>
                    <div>
                        <h4>Детям и взрослым</h4>
                        <p>Подбор формата и возрастных условий — в карточке конкретного сеанса.</p>
                    </div>
                </div>
                <div class="card feature" data-reveal>
                    <span class="feature__icon"><x-ico name="palette" /></span>
                    <div>
                        <h4>Разные техники</h4>
                        <p>Бумажная лоза, бисер, синельная проволока, фоамиран, макраме, канзаши — от 4 до 60 участников.
                        </p>
                    </div>
                </div>
                <div class="card feature" data-reveal>
                    <span class="feature__icon"><x-ico name="leaf" /></span>
                    <div>
                        <h4>В студии и на выезде</h4>
                        <p>Запись на опубликованный сеанс или запрос на выездное занятие.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-head">
                <h2>Популярные направления</h2>
                <p>Каталог показывает программу, мастера и ближайшие подтверждённые сеансы.</p>
            </div>
            <div class="grid grid-3">
                @foreach ($masterclasses as $masterclass)
                    @include('partials.masterclass-card', ['masterclass' => $masterclass])
                @endforeach
            </div>
            <div class="center" style="margin-top:32px">
                <a class="btn btn--ghost" href="{{ route('masterclasses.index') }}">Все мастер-классы</a>
            </div>
        </div>
    </section>

    <section class="section" style="background:rgba(255,255,255,.5)">
        <div class="container">
            <div class="section-head">
                <h2>Как проходит занятие</h2>
                <p>От выбора техники до готовой работы — с понятной подготовкой и поддержкой ведущего.</p>
            </div>
            <div class="steps">
                <div class="step" data-reveal>
                    <div class="step__num">1. Выберите</div>
                    <p class="muted" style="margin:0">Технику и формат.</p>
                </div>
                <div class="step" data-reveal>
                    <div class="step__num">2. Запишитесь</div>
                    <p class="muted" style="margin:0">Проверьте дату, место и условия.</p>
                </div>
                <div class="step" data-reveal>
                    <div class="step__num">3. Создайте</div>
                    <p class="muted" style="margin:0">Материалы и результат — в карточке сеанса.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-head">
                <h2>Ведущая студии</h2>
                <p>Сейчас занятия ведёт {{ $masters->first()?->name ?? 'Ирина Буренкова' }} — основатель и мастер
                    арт-студии.</p>
            </div>
            <div class="grid grid-2">
                @php $master = $masters->first(); @endphp
                @if ($master)
                    <div class="card master-card" data-reveal>
                        <img src="{{ asset($master->photo ?? 'images/photo-1.jpg') }}" alt="{{ $master->name }}">
                        <div>
                            <h3 style="margin-bottom:6px">{{ $master->name }}</h3>
                            <p class="lead-muted" style="margin-bottom:8px">{{ $master->title }}</p>
                            <p class="muted" style="margin:0;font-size:15px">{{ $master->specialization }}. Адрес и
                                расписание — в карточках сеансов.</p>
                        </div>
                    </div>
                @endif
                <div class="card" data-reveal>
                    <h3>Ищете занятие для группы?</h3>
                    <p class="muted">Для праздника, школы, детского сада или компании можно запросить выездной формат.
                        Параметры и стоимость студия подтверждает до заказа.</p>
                    <a class="btn" href="{{ route('outreach.index') }}">Рассчитать выездной формат</a>
                </div>
            </div>
        </div>
    </section>

    @if ($sessions->isNotEmpty())
        <section class="section section--tight">
            <div class="container">
                <div class="section-head">
                    <h2>Ближайшие сеансы</h2>
                    <p>Опубликованные сеансы в студии по адресу {{ setting('studio.address') }}.</p>
                </div>
                <div class="session-list">
                    @foreach ($sessions as $session)
                        @include('partials.session-item', ['session' => $session])
                    @endforeach
                </div>
                <div class="center" style="margin-top:28px">
                    <a class="btn btn--ghost" href="{{ route('masterclasses.index') }}">Смотреть расписание</a>
                </div>
            </div>
        </section>
    @endif

    @if ($reviews->isNotEmpty())
        <section class="section">
            <div class="container">
                <div class="section-head">
                    <h2>Отзывы</h2>
                    <p>Демонстрационные отзывы локальной версии помечены как «Демо-отзыв».</p>
                </div>
                @include('partials.reviews-slider', ['reviews' => $reviews])
                <div class="center" style="margin-top:28px">
                    <a class="btn btn--ghost" href="{{ route('reviews.index') }}">Все отзывы</a>
                </div>
            </div>
        </section>
    @endif

    <section class="section" style="background:rgba(255,255,255,.5)">
        <div class="container">
            <div class="section-head">
                <h2>Частые вопросы</h2>
                <p>Короткие ответы помогают выбрать формат и подготовиться.</p>
            </div>
            <div style="max-width:900px;margin:0 auto">
                <x-faq :items="[
                    [
                        'q' => 'Какие техники доступны?',
                        'a' =>
                            'Бумажная лоза, канзаши, макраме, фоамиран, бисер и синельная проволока. Полный список — в каталоге мастер-классов.',
                    ],
                    [
                        'q' => 'Как узнать ближайшую дату и стоимость?',
                        'a' =>
                            'Откройте карточку мастер-класса: там указаны дата, время, ведущий, место и цена. Если дат нет — уточните в студии.',
                    ],
                    [
                        'q' => 'Можно прийти с ребёнком?',
                        'a' => 'Возраст и условия участия указываются для каждого сеанса; заявку оформляет взрослый.',
                    ],
                    [
                        'q' => 'Можно купить работу или пройти курс онлайн?',
                        'a' => 'Для разделов «Магазин» и «Курсы» есть отдельные карточки, условия оплаты и доступа.',
                    ],
                ]" />
            </div>
        </div>
    </section>

    @include('partials.outreach-cta')
@endsection
