<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'AutoERP') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    </head>
    <body>
        <script>
            window.__ERP_BOOTSTRAP__ = @json([
                'appName' => config('app.name', 'AutoERP'),
                'apiBaseUrl' => url('/api'),
                'user' => auth()->check() ? [
                    'id' => auth()->id(),
                    'name' => auth()->user()->name,
                    'email' => auth()->user()->email,
                ] : null,
                'tenant' => null,
                'organizationUnit' => null,
            ]);
        </script>
        <div id="app"></div>
    </body>
</html>
