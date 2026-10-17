@extends('layouts.site')

@section('title', $lesson->title.' — '.$course->title)
@section('robots', 'noindex,nofollow')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Онлайн-курсы', 'url' => route('courses.index')],
        ['label' => $course->title, 'url' => route('courses.show', $course->slug)],
        ['label' => $lesson->title],
    ]" />
@endsection

@section('content')
    <section class="section section--tight">
        <div class="container">
            <div class="learn">
                <aside class="lesson-nav">
                    <h4 style="margin-bottom:12px">Программа</h4>
                    @foreach ($course->modules as $module)
                        <div class="module">
                            <div class="module__title">{{ $module->title }}</div>
                            @foreach ($module->lessons as $item)
                                <a href="{{ route('courses.learn', [$course->slug, $item->id]) }}"
                                   @class(['is-active' => $item->id === $lesson->id, 'is-done' => in_array($item->id, $completedLessonIds, true)])>
                                    <span class="dot"></span>
                                    <span>{{ $item->title }}</span>
                                    @if ($item->is_preview)<span class="badge badge--violet" style="margin-left:auto;font-size:10px">Free</span>@endif
                                </a>
                            @endforeach
                        </div>
                    @endforeach
                </aside>

                <div>
                    <div class="player">
                        <div class="player__stage">
                            <div class="play"><x-ico name="play" /></div>
                            <div style="position:absolute;bottom:18px;left:22px;right:22px;display:flex;justify-content:space-between;color:rgba(255,255,255,.85);font-size:13px">
                                <span>{{ $course->title }}</span>
                                <span>{{ $lesson->duration_minutes }} мин</span>
                            </div>
                        </div>
                    </div>

                    <div class="card" style="margin-top:20px">
                        <div class="meta-row" style="justify-content:space-between">
                            <span class="badge badge--violet">Урок</span>
                            <span class="muted">{{ $lesson->module->title }}</span>
                        </div>
                        <h1 style="margin-top:12px;font-size:clamp(24px,2.4vw,34px)">{{ $lesson->title }}</h1>
                        @if ($lesson->content)
                            <p>{{ $lesson->content }}</p>
                        @endif

                        <div class="note-strip" style="margin:16px 0">Платные материалы хранятся в приватном хранилище и доступны только при действующем доступе. Постоянная публичная ссылка не выдаётся.</div>

                        @if ($enrollment && $enrollment->hasActiveAccess())
                            @php $done = in_array($lesson->id, $completedLessonIds, true); @endphp
                            <form action="{{ route('courses.complete', [$course->slug, $lesson->id]) }}" method="post">
                                @csrf
                                <button class="btn" type="submit" @disabled($done)>{{ $done ? 'Урок пройден' : 'Отметить как пройденный' }}</button>
                            </form>
                        @elseif ($lesson->is_preview)
                            <a class="btn btn--ghost" href="{{ route('courses.show', $course->slug) }}">Записаться на курс</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
