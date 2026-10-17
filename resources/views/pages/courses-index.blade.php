@extends('layouts.site')

@section('title', 'Онлайн-курсы — '.setting('studio.name', 'Корни'))
@section('meta_description', 'Онлайн-курсы по творческим техникам: программа, автор, цена и срок доступа. Доступ открывается после подтверждения оплаты.')
@section('canonical', route('courses.index'))

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => 'Онлайн-курсы']]" />
@endsection

@section('content')
    <section class="hero">
        <div class="container">
            <div class="hero__inner" style="min-height:auto">
                <div class="hero__content" data-reveal>
                    <h1>Онлайн-курсы</h1>
                    <p class="lead">Учитесь в своём темпе: программа по модулям, бесплатный вводный урок и доступ к платным материалам после подтверждения оплаты.</p>
                </div>
                <div class="hero__media" style="height:auto"><img src="{{ asset('images/hero-masterclass.png') }}" alt="Онлайн-курс" loading="lazy"></div>
            </div>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container">
            @if ($courses->isEmpty())
                <div class="empty">
                    <x-ico name="video" />
                    <h3>Курсы скоро появятся</h3>
                    <p>Сейчас мы готовим новые программы. Загляните позже.</p>
                </div>
            @else
                <div class="grid grid-3">
                    @foreach ($courses as $course)
                        @include('partials.course-card', ['course' => $course])
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
