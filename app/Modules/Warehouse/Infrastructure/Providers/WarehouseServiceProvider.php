<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Warehouse\Application\Repositories\WarehouseLocationRepositoryInterface;
use Modules\Warehouse\Application\Repositories\WarehouseRepositoryInterface;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseLocationModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories\EloquentWarehouseLocationRepository;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories\EloquentWarehouseRepository;

final class WarehouseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/warehouse.php', 'warehouse');
        $this->app->singleton(WarehouseRepositoryInterface::class, function (): WarehouseRepositoryInterface {
            return new EloquentWarehouseRepository(new WarehouseModel);
        });
        $this->app->singleton(WarehouseLocationRepositoryInterface::class, function (): WarehouseLocationRepositoryInterface {
            return new EloquentWarehouseLocationRepository(new WarehouseLocationModel);
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Eloquent/Migrations');
    }
}
