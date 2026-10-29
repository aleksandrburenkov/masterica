@extends('layouts.site')

@section('title', 'Отзывы — '.setting('studio.name', 'Корни'))
@section('meta_description', 'Отзывы о занятиях студии. Демонстрационные отзывы локальной версии помечены как «Демо-отзыв».')
@section('canonical', route('reviews.index'))

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => 'Отзывы']]" />
@endsection

@push('head')
    <link rel="stylesheet" href="{{ asset('css/site/modules/review-video.css') }}">
@endpush

@section('content')
    <section class="hero">
        <div class="container">
            <div class="hero__inner">
                <div class="hero__content" data-reveal>
                    <h1>Отзывы</h1>
                    <p class="lead">Отзывы проходят модерацию до публикации. Синтетические демонстрационные отзывы помечены как «Демо-отзыв».</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container">
            @if ($reviews->isEmpty())
                <div class="empty">
                    <x-ico name="star" />
                    <h3>Отзывов пока нет</h3>
                    <p>Станьте первым — поделитесь впечатлением о занятии.</p>
                </div>
            @else
                <div class="grid grid-3">
                    @foreach ($reviews as $review)
                        @include('partials.review-card', ['review' => $review])
                    @endforeach
                </div>
                <div style="margin-top:36px">{{ $reviews->links() }}</div>
            @endif
        </div>
    </section>

    <section class="section" style="background:rgba(255,255,255,.5)" id="leave-review">
        <div class="container" style="max-width:760px">
            <div class="section-head"><h2>Оставить отзыв</h2><p>Текст или видео. Все отзывы проверяются модератором.</p></div>

            @auth
                <form class="card" action="{{ route('reviews.store') }}" method="post" enctype="multipart/form-data" data-ajax-form>
                    @csrf
                    <div class="grid grid-2" style="gap:14px">
                        <div class="field">
                            <label for="r-type">Тип отзыва</label>
                            <select id="r-type" class="select" name="type" required>
                                <option value="text">Текстовый</option>
                                <option value="video">Видео (файл или ссылка)</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="r-rating">Оценка</label>
                            <select id="r-rating" class="select" name="rating">
                                <option value="5">5 — отлично</option>
                                <option value="4">4 — хорошо</option>
                                <option value="3">3 — нормально</option>
                                <option value="2">2 — так себе</option>
                                <option value="1">1 — плохо</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-2" style="gap:14px">
                        <div class="field">
                            <label for="r-class">Мастер-класс</label>
                            <select id="r-class" class="select" name="masterclass_id">
                                <option value="">Не указывать</option>
                                @foreach ($masterclasses as $masterclass)
                                    <option value="{{ $masterclass->id }}">{{ $masterclass->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="r-city">Город</label>
                            <input id="r-city" class="input" type="text" name="city" value="{{ old('city', setting('studio.city')) }}">
                        </div>
                    </div>
                    <div class="field">
                        <label for="r-body">Текст отзыва</label>
                        <textarea id="r-body" class="textarea @error('body') is-invalid @enderror" name="body" minlength="10" required>{{ old('body') }}</textarea>
                    </div>
                    <div class="field">
                        <label for="r-video-file">Видеоотзыв (видеофайл)</label>
                        <div class="video-drop" data-video-drop
                             data-video-max="{{ (int) config('video.max_size_kb', 204800) }}"
                             data-video-accept="{{ implode(',', (array) config('video.allowed_extensions', [])) }}">
                            <button type="button" class="video-drop__body" data-video-drop-body>
                                <span class="video-drop__icon" aria-hidden="true"><x-ico name="video" /></span>
                                <span class="video-drop__title">Перетащите видео сюда или <span class="video-drop__link">выберите файл</span></span>
                                <span class="video-drop__hint">MP4, MOV, WebM, MKV, AVI · до {{ round((int) config('video.max_size_kb', 204800) / 1024) }} МБ</span>
                            </button>
                            <div class="video-drop__error" data-video-drop-error hidden></div>
                            <div class="video-drop__preview" data-video-drop-preview hidden>
                                <video class="video-drop__player" data-video-drop-player muted playsinline preload="metadata"></video>
                                <div class="video-drop__meta">
                                    <span class="video-drop__name" data-video-drop-name></span>
                                    <button type="button" class="video-drop__remove" data-video-drop-remove>
                                        <x-ico name="close" /> Удалить
                                    </button>
                                </div>
                            </div>
                            <input class="video-drop__input" type="file" id="r-video-file" name="video_file"
                                   accept=".mp4,.mov,.m4v,.webm,.mkv,.avi,.ogv,video/*"
                                   data-video-drop-input hidden>
                        </div>
                        <p class="hint">Файл проверяется на вирусы и вредоносные вложения, затем проходит модерацию. Длительность — до {{ intdiv((int) config('video.max_duration_seconds', 600), 60) }} мин.</p>
                        @error('video_file')<div class="error-text">{{ $message }}</div>@enderror
                    </div>
                    <div class="field">
                        <label for="r-video">Или ссылка на видео</label>
                        <input id="r-video" class="input" type="url" name="video_url" value="{{ old('video_url') }}" placeholder="https://">
                        @error('video_url')<div class="error-text">{{ $message }}</div>@enderror
                    </div>
                    <div class="field">
                        <label class="checkbox">
                            <input type="checkbox" name="consent_data" value="1" required>
                            <span>
                                {{ legal('consent_review_data', 'lead', 'Даю согласие на обработку персональных данных для рассмотрения и публикации отзыва') }}.
                                <a href="{{ route('legal.show', legal('consent_personal_data', 'slug', 'soglasie-na-obrabotku-dannyh')) }}" target="_blank" rel="noopener">Условия обработки</a>
                            </span>
                        </label>
                        @error('consent_data')<div class="error-text">{{ $message }}</div>@enderror
                        <label class="checkbox" style="margin-top:8px">
                            <input type="checkbox" name="consent_publish" value="1" required>
                            <span>
                                {{ legal('consent_review', 'lead', 'Даю согласие на публикацию отзыва и использование моего имени, текста, фото- и видеоматериалов') }}.
                                <a href="{{ route('legal.show', legal('review_consent', 'slug', 'soglasie-na-publikaciyu-otzyva')) }}" target="_blank" rel="noopener">Подробнее</a>
                            </span>
                        </label>
                        <label class="checkbox" style="margin-top:8px">
                            <input type="checkbox" name="consent_marketing" value="1">
                            <span>{{ legal('consent_marketing', 'lead', 'Согласен(на) получать новости и предложения студии (необязательно)') }}</span>
                        </label>
                    </div>
                    <button class="btn btn--block" type="submit">Отправить на модерацию</button>
                    <p class="form-note" style="margin-top:14px">Согласие на публикацию можно отозвать в любое время; после отзыва публикация удаляется или обезличивается.</p>
                </form>
            @else
                <div class="card center">
                    <p class="lead-muted">Оставлять отзывы могут только зарегистрированные пользователи. Войдите — и вернитесь к форме.</p>
                    <a class="btn" href="{{ route('login', ['redirect' => route('reviews.index').'#leave-review']) }}">Войти и оставить отзыв</a>
                    <p class="form-note" style="margin-top:14px">Нет аккаунта? <a href="{{ route('register') }}">Создайте</a> — это займёт минуту.</p>
                </div>
            @endauth
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('js/site/modules/review-video-upload.js') }}" defer></script>
@endpush
