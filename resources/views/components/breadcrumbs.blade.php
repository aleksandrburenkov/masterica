@props(['items' => []])

@php
    $all = array_merge([['label' => 'Главная', 'url' => route('home')]], $items);
    $structured = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [],
    ];
    foreach ($all as $i => $crumb) {
        $structured['itemListElement'][] = [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'name' => $crumb['label'],
            'item' => $crumb['url'] ?? null,
        ];
    }
@endphp

<nav class="breadcrumbs" aria-label="Хлебные крошки">
    <ol>
        @foreach ($all as $crumb)
            <li>
                @if (!empty($crumb['url']) && !$loop->last)
                    <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
                @else
                    <span aria-current="page">{{ $crumb['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>

@push('structured')
    <script type="application/ld+json">{!! json_encode($structured, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
