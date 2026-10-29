@props(['video'])

@php
    $badge = function (string $text, string $tone): string {
        $colors = [
            'success' => ['#e7f7ee', '#1c7a4a', '#bfead3'],
            'warning' => ['#fff6e5', '#9a6b00', '#f3dcae'],
            'danger' => ['#fdecec', '#a12b2b', '#f4c3c3'],
            'gray' => ['#f2f3f7', '#475069', '#e1e4ee'],
        ];
        [$bg, $fg, $border] = $colors[$tone] ?? $colors['gray'];

        return 'display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:600;background:'.$bg.';color:'.$fg.';border:1px solid '.$border;
    };

    $statusTone = match ($video->status->value) {
        'ready' => 'success',
        'quarantined', 'failed' => 'danger',
        'pending' => 'warning',
        default => 'gray',
    };

    $scanTone = match ($video->scan_status->value) {
        'clean' => 'success',
        'suspicious', 'infected' => 'danger',
        'error' => 'warning',
        default => 'gray',
    };
@endphp

<div style="display:grid;gap:12px">
    <div style="display:flex;flex-wrap:wrap;gap:8px">
        <span style="{{ $badge('Обработка: '.$video->status->label(), $statusTone) }}">
            Обработка: {{ $video->status->label() }}
        </span>
        <span style="{{ $badge('Проверка: '.$video->scan_status->label(), $scanTone) }}">
            Проверка: {{ $video->scan_status->label() }}@if ($video->scanner) · {{ $video->scanner }}@endif
        </span>
    </div>

    @if ($video->isPlayable())
        <video controls preload="none" playsinline
               style="width:100%;max-width:520px;border-radius:12px;background:#10142e"
               @if ($video->posterUrl()) poster="{{ $video->posterUrl() }}" @endif>
            <source src="{{ $video->streamUrl() }}" type="{{ $video->mime_type ?: 'video/mp4' }}">
        </video>
    @endif

    <dl style="display:grid;grid-template-columns:auto 1fr;gap:4px 16px;margin:0;font-size:13px">
        <dt style="color:#6b7280">Файл</dt>
        <dd style="margin:0">{{ $video->original_name ?: '—' }}</dd>

        <dt style="color:#6b7280">Формат</dt>
        <dd style="margin:0">{{ $video->mime_type ?: '—' }}{{ $video->extension ? ' (.'.$video->extension.')' : '' }}</dd>

        <dt style="color:#6b7280">Размер</dt>
        <dd style="margin:0">{{ $video->humanSize() }}</dd>

        <dt style="color:#6b7280">Длительность</dt>
        <dd style="margin:0">{{ $video->durationLabel() }}</dd>

        <dt style="color:#6b7280">Разрешение</dt>
        <dd style="margin:0">{{ $video->resolutionLabel() }}</dd>

        <dt style="color:#6b7280">SHA-256</dt>
        <dd style="margin:0;word-break:break-all">{{ $video->checksum ?: '—' }}</dd>
    </dl>

    @if ($video->scan_report)
        <p style="margin:0;font-size:12px;color:#6b7280">Отчёт проверки: {{ $video->scan_report }}</p>
    @endif

    <p style="margin:0;font-size:12px;color:#6b7280">
        При публикации видео переносится в каталог видео и оптимизируется, если файл крупный.
        При отклонении видео удаляется без возможности восстановления — текст отзыва сохраняется.
    </p>
</div>
