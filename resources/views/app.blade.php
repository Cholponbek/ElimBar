<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>{{ $meta['title'] ?? config('app.name', 'ElimBar') }}</title>

    {{--
        Open Graph/Twitter Card — не через Vue <Head>: тут нет Inertia SSR,
        значит краулеры (Instagram, Telegram, WhatsApp, Facebook) видят
        только этот, ещё не гидрированный HTML, и никогда не выполнят JS,
        который дописал бы теги после загрузки. $meta приходит через
        View::share() из контроллера (см. CaseController::shareMeta).
    --}}
    <meta name="description" content="{{ $meta['description'] ?? 'Каждый сом привязан к конкретному кейсу — публичный отчёт собирается автоматически.' }}">
    <meta property="og:type" content="{{ $meta['type'] ?? 'website' }}">
    <meta property="og:site_name" content="{{ config('app.name', 'ElimBar') }}">
    <meta property="og:title" content="{{ $meta['title'] ?? config('app.name', 'ElimBar') }}">
    <meta property="og:description" content="{{ $meta['description'] ?? 'Каждый сом привязан к конкретному кейсу — публичный отчёт собирается автоматически.' }}">
    <meta property="og:url" content="{{ $meta['url'] ?? url()->current() }}">
    @if (! empty($meta['image']))
        <meta property="og:image" content="{{ $meta['image'] }}">
        <meta name="twitter:card" content="summary_large_image">
    @else
        <meta name="twitter:card" content="summary">
    @endif
    <meta name="twitter:title" content="{{ $meta['title'] ?? config('app.name', 'ElimBar') }}">
    <meta name="twitter:description" content="{{ $meta['description'] ?? 'Каждый сом привязан к конкретному кейсу — публичный отчёт собирается автоматически.' }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="antialiased">
    @inertia
</body>
</html>
