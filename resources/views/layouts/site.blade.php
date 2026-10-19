<!doctype html>
<html lang="ru">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', setting('studio.name', 'Корни') . ' — арт-студия в ' . setting('studio.city', 'Брянске'))</title>
    <meta name="description" content="@yield('meta_description', setting('studio.og_description'))">
    <meta name="robots" content="@yield('robots', 'index,follow')">
    <link rel="canonical" href="@yield('canonical', url()->current())">

    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:site_name" content="{{ setting('studio.name', 'Корни') }}">
    <meta property="og:title" content="@yield('title', setting('studio.name', 'Корни'))">
    <meta property="og:description" content="@yield('meta_description', setting('studio.og_description'))">
    <meta property="og:url" content="@yield('canonical', url()->current())">
    @hasSection('og_image')
        <meta property="og:image" content="@yield('og_image')">
    @endif

    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="preload" href="{{ asset('fonts/gothampro.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/gothampro_bold.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/site/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/site/components.css') }}">
    <link rel="stylesheet" href="{{ asset('css/site/pages.css') }}">
    @stack('head')
</head>

<body class="@yield('body_class')">
    <a class="skip-link" href="#main">Перейти к содержанию</a>

    @include('partials.header')

    @include('partials.flash')

    <main id="main">
        @hasSection('breadcrumbs')
            <div class="container">
                @yield('breadcrumbs')
            </div>
        @endif

        @yield('content')
    </main>

    @include('partials.footer')
    @include('partials.to-top')

    @stack('structured')
    <script src="{{ asset('js/site/app.js') }}" defer></script>
    @stack('scripts')
</body>

</html>
