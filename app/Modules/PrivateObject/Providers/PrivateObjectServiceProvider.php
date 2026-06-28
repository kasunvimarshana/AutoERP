<?php

declare(strict_types=1);

namespace Modules\PrivateObject\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Modules\PrivateObject\Contracts\PrivateObjectStorageInterface;
use Modules\PrivateObject\Services\PrivateObjectStorageService;

final class PrivateObjectServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/private-object.php', 'private-object');
        $this->app->bind(PrivateObjectStorageInterface::class, PrivateObjectStorageService::class);
        $this->app->when(PrivateObjectStorageService::class)
            ->needs('$defaultDisk')
            ->give(static fn (): string => (string) Config::get('private-object.default_disk'));
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../Config/private-object.php' => config_path('private-object.php'),
        ], 'private-object-config');
    }
}
