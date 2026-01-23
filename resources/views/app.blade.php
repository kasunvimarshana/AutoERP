<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'ModularSaaS') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Styles -->
    @if (app()->environment('testing') || !file_exists(public_path('build/manifest.json')))
        <!-- Testing environment or manifest not built - serve minimal HTML -->
        <style>
            body { font-family: 'figtree', sans-serif; padding: 2rem; text-align: center; }
            .loading { margin-top: 2rem; }
        </style>
    @else
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="antialiased">
    <div id="app">
        @if (app()->environment('testing') || !file_exists(public_path('build/manifest.json')))
            <h1>ModularSaaS Application</h1>
            <p class="loading">Vue.js frontend will be available after running: npm run build</p>
        @endif
    </div>
</body>
</html>
