@extends('layouts.site')

@section('title', 'Мои курсы — '.setting('studio.name', 'Корни'))
@section('robots', 'noindex,nofollow')

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => 'Личный кабинет', 'url' => route('account.dashboard')], ['label' => 'Мои курсы']]" />
@endsection

@section('content')
    <section class="section section--tight">
        <div class="container">
            <div class="account">
                @include('partials.account-nav')

                <div>
                    <h2>Мои курсы</h2>
                    <p class="muted">Доступ и прогресс по курсам.</p>

                    @if ($enrollments->isEmpty())
                        <div class="empty">
                            <x-ico name="video" />
                            <h3>Курсов пока нет</h3>
                            <p>Выберите курс — доступ откроется после подтверждения оплаты.</p>
                            <a class="btn" href="{{ route('courses.index') }}">Смотреть курсы</a>
                        </div>
                    @else
                        <div class="stack">
                            @foreach ($enrollments as $row)
                                @php $enrollment = $row['enrollment']; $course = $enrollment->course; @endphp
                                <div class="card">
                                    <div style="display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap">
                                        <div>
                                            <h3 style="margin-bottom:4px">{{ $course->title }}</h3>
                                            <div class="meta-row">
                                                <span>{{ $course->modules()->count() }} модулей</span>
                                                <span>доступ до {{ optional($enrollment->expires_at)->translatedFormat('d.m.Y') ?? '—' }}</span>
                                            </div>
                                        </div>
                                        <span class="badge {{ $enrollment->hasActiveAccess() ? 'badge--green' : 'badge--amber' }}">{{ $enrollment->status->label() }}</span>
                                    </div>

                                    @if ($enrollment->hasActiveAccess())
                                        <div class="progress-bar" style="margin:16px 0 8px"><span style="width:{{ $row['progress'] }}%"></span></div>
                                        <div class="muted" style="font-size:13px;margin-bottom:14px">Прогресс: {{ $row['progress'] }}%</div>
                                        <a class="btn btn--sm" href="{{ route('courses.learn', [$course->slug, $course->modules()->first()?->lessons()->first()?->id]) }}">Продолжить обучение</a>
                                    @elseif ($enrollment->status->value === 'pending_payment')
                                        <div class="note-strip" style="margin-top:14px">Ожидает подтверждения оплаты администратором. Реальное списание не выполняется.</div>
                                    @else
                                        <p class="muted" style="margin-top:14px">Доступ по этому курсу неактивен.</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <p class="form-note" style="margin-top:16px">Материалы курсов предоставляются в рамках <a href="{{ route('legal.show', legal('license_terms', 'slug', 'licenzionnoe-soglashenie')) }}" target="_blank" rel="noopener">Лицензионного соглашения</a>: копирование, передача третьим лицам и коммерческое использование материалов запрещены.</p>
                </div>
            </div>
        </div>
    </section>
@endsection
