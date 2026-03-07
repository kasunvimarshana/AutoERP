<?php
return [
    'name' => env('APP_NAME', 'OrderService'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost:8005'),
    'timezone' => 'UTC',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'faker_locale' => 'en_US',
    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY'),
    'previous_keys' => [],
    'maintenance' => ['driver' => 'file'],
    'providers' => \Illuminate\Support\ServiceProvider::defaultProviders()->merge([
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        App\Providers\RepositoryServiceProvider::class,
        App\Providers\EventServiceProvider::class,
    ])->toArray(),
    'aliases' => \Illuminate\Support\Facades\Facade::defaultAliases()->toArray(),
];
