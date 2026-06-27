<?php

declare(strict_types=1);

namespace Modules\PrivateObject\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Modules\PrivateObject\Contracts\PrivateObjectStorageInterface;
use Modules\PrivateObject\Services\PrivateObjectStorage;

final class PrivateObjectServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/private_object.php', 'private-object');
        $this->app->bind(PrivateObjectStorageInterface::class, PrivateObjectStorage::class);
        $this->app->when(PrivateObjectStorage::class)
            ->needs('$defaultDisk')
            ->give(static fn (): string => (string) Config::get('private-object.default_disk'));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
