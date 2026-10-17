@extends('layouts.site')

@section('title', $page->seo_title ?: $page->title)
@section('meta_description', $page->seo_description ?: $page->excerpt)
@section('canonical', route('guides.show', $page->slug))
@if ($page->image)
    @section('og_image', asset($page->image))
@endif

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => 'Материалы', 'url' => route('guides.index')], ['label' => $page->title]]" />
@endsection

@push('structured')
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $page->title,
            'description' => $page->excerpt,
            'datePublished' => optional($page->published_at)->toIso8601String(),
            'author' => ['@type' => 'Organization', 'name' => setting('studio.name', 'Корни')],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
@endpush

@section('content')
    <section class="section section--tight">
        <div class="container" style="max-width:900px">
            <article class="card">
                @if ($page->image)
                    <img src="{{ asset($page->image) }}" alt="{{ $page->title }}" style="border-radius:var(--radius);margin-bottom:22px;aspect-ratio:16/9;object-fit:cover">
                @endif
                <span class="chip">{{ $page->type === 'guide' ? 'Руководство' : 'Материал' }}</span>
                <h1 style="margin-top:12px">{{ $page->title }}</h1>
                <p class="lead-muted">{{ $page->excerpt }}</p>
                <div style="color:var(--ink-soft)">{!! nl2br(e($page->body)) !!}</div>
            </article>

            @if ($others->isNotEmpty())
                <div class="grid grid-2" style="margin-top:28px">
                    @foreach ($others as $other)
                        <a class="card" href="{{ route('guides.show', $other->slug) }}">
                            <span class="chip">Материал</span>
                            <h3 style="margin-top:10px;font-size:20px">{{ $other->title }}</h3>
                            <p class="muted" style="margin:0">{{ $other->excerpt }}</p>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
