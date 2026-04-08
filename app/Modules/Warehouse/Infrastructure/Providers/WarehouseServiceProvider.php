<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Warehouse\Application\Contracts\WarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Contracts\WarehouseServiceInterface;
use Modules\Warehouse\Application\Services\WarehouseLocationService;
use Modules\Warehouse\Application\Services\WarehouseService;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseLocationRepositoryInterface;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseRepositoryInterface;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories\EloquentWarehouseLocationRepository;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories\EloquentWarehouseRepository;

class WarehouseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WarehouseRepositoryInterface::class, EloquentWarehouseRepository::class);
        $this->app->bind(WarehouseLocationRepositoryInterface::class, EloquentWarehouseLocationRepository::class);
        $this->app->bind(WarehouseServiceInterface::class, WarehouseService::class);
        $this->app->bind(WarehouseLocationServiceInterface::class, WarehouseLocationService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
