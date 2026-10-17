@extends('layouts.site')

@php
    $documents = collect($legalDocuments ?? [])->values();
@endphp

@section('title', $document->title . ' — ' . setting('studio.name', 'Корни'))
@section('meta_description', $document->lead ?: $document->title)
@section('canonical', route('legal.show', $document->slug))

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => $document->title]]" />
@endsection

@push('head')
    <style>
        .legal-body h2 {
            font-size: 20px;
            margin: 26px 0 10px;
            color: var(--ink);
        }

        .legal-body h2:first-child {
            margin-top: 0;
        }

        .legal-body p {
            margin: 0 0 12px;
            line-height: 1.65;
        }

        .legal-body ul {
            margin: 0 0 12px;
            padding-left: 22px;
            display: grid;
            gap: 6px;
        }
    </style>
@endpush

@section('content')
    <section class="section section--tight">
        <div class="container">
            <div class="card">
                <span class="chip chip--violet">Правовой документ</span>
                <h1 style="margin-top:12px">{{ $document->title }}</h1>
                @if ($document->lead)
                    <p class="lead-muted">{{ $document->lead }}</p>
                @endif

                <div class="meta-row" style="margin-top:8px">
                    @if ($document->version)
                        <span class="muted">Редакция: {{ $document->version }}</span>
                    @endif
                    @if ($document->effective_date)
                        <span class="muted">В силе с {{ $document->effective_date->translatedFormat('d.m.Y') }}</span>
                    @endif
                </div>

                <div class="legal-body" style="margin-top:20px;color:var(--ink-soft)">
                    {!! $document->body !!}
                </div>
            </div>

            @if ($documents->isNotEmpty())
                <div class="card" style="margin-top:20px">
                    <h3>Другие правовые документы</h3>
                    <ul class="list-check">
                        @foreach ($documents as $item)
                            @continue($item->key === $document->key)
                            <li>
                                <x-ico name="check-circle" />
                                <a href="{{ route('legal.show', $item->slug) }}">{{ $item->title }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </section>
@endsection
