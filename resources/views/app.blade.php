<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @php($site = app(\App\Services\Settings::class)->site())
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title inertia>{{ $site['name'] }}</title>
        <meta name="description" content="{{ $site['description'] }}">
        <meta name="theme-color" content="#ffffff">
        <link rel="icon" href="/favicon.ico" sizes="32x32">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <meta property="og:site_name" content="{{ $site['name'] }}">
        <meta property="og:title" content="{{ $site['name'] }}">
        <meta property="og:description" content="{{ $site['description'] }}">
        <meta property="og:image" content="{{ asset('brand/og-image.png') }}">
        <meta property="og:type" content="website">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300..800&display=swap" rel="stylesheet">
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>
