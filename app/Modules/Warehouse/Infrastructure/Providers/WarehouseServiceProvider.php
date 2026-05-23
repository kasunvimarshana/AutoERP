<?php

namespace Modules\Warehouse\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Warehouse\Application\Repositories\WarehouseLocationRepositoryInterface;
use Modules\Warehouse\Application\Repositories\WarehouseRepositoryInterface;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories\EloquentWarehouseLocationRepository;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories\EloquentWarehouseRepository;

class WarehouseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach (
            [
                WarehouseLocationRepositoryInterface::class => EloquentWarehouseLocationRepository::class,
                WarehouseRepositoryInterface::class => EloquentWarehouseRepository::class,
            ] as $interface => $implementation
        ) {
            $this->app->bind($interface, $implementation);
        }
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../Infrastructure/Persistence/Eloquent/Migrations');
    }
}
