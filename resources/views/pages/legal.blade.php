@extends('layouts.site')

@section('title', $title.' — '.setting('studio.name', 'Корни'))
@section('meta_description', $lead)

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => $title]]" />
@endsection

@section('content')
    <section class="section section--tight">
        <div class="container" style="max-width:860px">
            <div class="card">
                <span class="chip chip--violet">Демонстрационный документ</span>
                <h1 style="margin-top:12px">{{ $title }}</h1>
                <p class="lead-muted">{{ $lead }}</p>

                <div class="stack" style="margin-top:20px">
                    @foreach ($sections as $section)
                        <div>
                            <h3>{{ $section['h'] }}</h3>
                            <p>{{ $section['p'] }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="note-strip" style="margin-top:20px">
                    Это локальный демонстрационный текст и он не является утверждённой офертой или юридическим согласием. Перед публикацией владелец предоставит финальные документы.
                </div>
            </div>
        </div>
    </section>
@endsection
