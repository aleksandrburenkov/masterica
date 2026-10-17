@props(['name' => 'sparkles', 'size' => null])

@php
    $attrs = [
        'width' => $size,
        'height' => $size,
        'viewBox' => '0 0 24 24',
        'fill' => 'none',
        'stroke' => 'currentColor',
        'stroke-width' => '1.8',
        'stroke-linecap' => 'round',
        'stroke-linejoin' => 'round',
        'aria-hidden' => 'true',
    ];
@endphp

<svg {{ $attributes->merge($attrs) }}>
    @switch($name)
        @case('search')
            <circle cx="11" cy="11" r="7" /><path d="m21 21-4.3-4.3" />
            @break
        @case('close')
            <path d="M18 6 6 18M6 6l12 12" />
            @break
        @case('user')
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" /><circle cx="12" cy="7" r="4" />
            @break
        @case('arrow-up')
            <path d="m12 19V5M5 12l7-7 7 7" />
            @break
        @case('arrow-right')
            <path d="M5 12h14M13 6l6 6-6 6" />
            @break
        @case('chevron-down')
            <path d="m6 9 6 6 6-6" />
            @break
        @case('chevron-right')
            <path d="m9 6 6 6-6 6" />
            @break
        @case('check')
            <path d="M20 6 9 17l-5-5" />
            @break
        @case('check-circle')
            <circle cx="12" cy="12" r="9" /><path d="m9 12 2 2 4-4" />
            @break
        @case('phone')
            <path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2 4.2 2 2 0 0 1 4 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2Z" />
            @break
        @case('clock')
            <circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" />
            @break
        @case('pin')
            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" /><circle cx="12" cy="10" r="3" />
            @break
        @case('calendar')
            <rect x="3" y="4" width="18" height="18" rx="2" /><path d="M16 2v4M8 2v4M3 10h18" />
            @break
        @case('cart')
            <circle cx="9" cy="21" r="1" /><circle cx="20" cy="21" r="1" /><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6" />
            @break
        @case('star')
            <path d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.8-6.2-3.3-6.2 3.3L7 14.2l-5-4.9 6.9-1Z" />
            @break
        @case('play')
            <circle cx="12" cy="12" r="9" /><path d="m10 8 6 4-6 4Z" />
            @break
        @case('lock')
            <rect x="4" y="11" width="16" height="10" rx="2" /><path d="M8 11V7a4 4 0 0 1 8 0v4" />
            @break
        @case('sparkles')
            <path d="M12 3v4M12 17v4M3 12h4M17 12h4M6.3 6.3l2.4 2.4M15.3 15.3l2.4 2.4M17.7 6.3l-2.4 2.4M8.7 15.3l-2.4 2.4" />
            @break
        @case('users')
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M22 21v-2a4 4 0 0 0-3-3.9M16 3.1A4 4 0 0 1 16 11" />
            @break
        @case('palette')
            <path d="M12 22a10 10 0 1 1 0-20c5 0 9 3.6 9 8 0 2.2-1.8 4-4 4h-2a2 2 0 0 0-2 2c0 .6.2 1 .5 1.5.3.5.5.9.5 1.5 0 1.1-1 2-2 2Z" /><circle cx="7.5" cy="11" r="1" /><circle cx="12" cy="8" r="1" /><circle cx="16.5" cy="11" r="1" />
            @break
        @case('leaf')
            <path d="M11 20A7 7 0 0 1 4 13c0-6 5-10 16-10 0 9-4 14-9 14Z" /><path d="M4 21c4-6 7-8 12-9" />
            @break
        @case('plus')
            <path d="M12 5v14M5 12h14" />
            @break
        @case('shield')
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" />
            @break
        @case('mail')
            <rect x="2" y="4" width="20" height="16" rx="2" /><path d="m2 7 10 6 10-6" />
            @break
        @case('gift')
            <rect x="3" y="8" width="18" height="13" rx="2" /><path d="M12 8v13M3 12h18M12 8S10 2 7.5 3.5 9 8 12 8Zm0 0s2-6 4.5-4.5S15 8 12 8Z" />
            @break
        @case('video')
            <rect x="2" y="6" width="14" height="12" rx="2" /><path d="m16 10 6-3v10l-6-3Z" />
            @break
        @case('trash')
            <path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
            @break
        @case('edit')
            <path d="M12 20h9" /><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
            @break
        @case('logout')
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" />
            @break
        @case('info')
            <circle cx="12" cy="12" r="9" /><path d="M12 16v-4M12 8h.01" />
            @break
        @case('box')
            <path d="M21 8 12 3 3 8v8l9 5 9-5Z" /><path d="m3 8 9 5 9-5M12 13v8" />
            @break
        @case('award')
            <circle cx="12" cy="8" r="6" /><path d="M8.2 13.9 7 22l5-3 5 3-1.2-8.1" />
            @break
        @case('heart')
            <path d="M20.8 5.6a5.5 5.5 0 0 0-7.8 0L12 6.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 22l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z" />
            @break
        @case('eye')
            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" /><circle cx="12" cy="12" r="3" />
            @break
        @case('eye-off')
            <path d="M9.9 4.2A10.1 10.1 0 0 1 12 4c6.5 0 10 7 10 7a17.6 17.6 0 0 1-2.4 3.4M6.3 6.3A17.6 17.6 0 0 0 2 11s3.5 7 10 7a10.1 10.1 0 0 0 4-.8" /><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2" /><path d="m2 2 20 20" />
            @break
        @default
            <circle cx="12" cy="12" r="9" />
    @endswitch
</svg>
