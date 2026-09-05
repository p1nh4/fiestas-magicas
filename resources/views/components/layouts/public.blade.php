@props([
    'title' => null,
    'description' => null,
    'image' => null,
])

<!DOCTYPE html>
<html lang="{{ \App\Support\Locales::hreflang(app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <x-seo :title="$title" :description="$description" :image="$image" />

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('img/icon-180.png') }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <meta name="theme-color" content="#fdf7f5">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-ground text-ink antialiased">
    <a href="#contenido" class="skip-link">{{ __('site.nav.skip') }}</a>

    <x-util-bar />
    <x-site-header />

    <main id="contenido">
        {{ $slot }}
    </main>

    <x-site-footer />
    <x-whatsapp-dock />
</body>
</html>
