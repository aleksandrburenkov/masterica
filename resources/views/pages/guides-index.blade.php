@extends('layouts.site')

@section('title', 'Полезные материалы — '.setting('studio.name', 'Корни'))
@section('meta_description', 'Авторские материалы и руководства по творческим техникам.')
@section('canonical', route('guides.index'))

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => 'Материалы']]" />
@endsection

@section('content')
    <section class="section section--tight">
        <div class="container">
            <div class="section-head" style="text-align:left;margin-left:0">
                <span class="eyebrow">Полезное</span>
                <h1>Материалы и руководства</h1>
                <p>Авторские статьи, которые помогают подготовиться к занятиям и продолжить практику дома.</p>
            </div>

            @if ($guides->isEmpty())
                <div class="empty"><x-ico name="info" /><h3>Материалы скоро появятся</h3></div>
            @else
                <div class="grid grid-2">
                    @foreach ($guides as $guide)
                        <article class="card card--pad-0" data-reveal>
                            @if ($guide->image)
                                <a href="{{ route('guides.show', $guide->slug) }}"><img src="{{ media_url($guide->image) }}" alt="{{ $guide->title }}" loading="lazy" style="aspect-ratio:16/9;object-fit:cover"></a>
                            @endif
                            <div style="padding:22px">
                                <span class="chip">{{ $guide->type === 'guide' ? 'Руководство' : 'Статья' }}</span>
                                <h3 style="margin-top:12px"><a href="{{ route('guides.show', $guide->slug) }}">{{ $guide->title }}</a></h3>
                                <p class="muted">{{ $guide->excerpt }}</p>
                                <a class="card-link" href="{{ route('guides.show', $guide->slug) }}">Читать</a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
