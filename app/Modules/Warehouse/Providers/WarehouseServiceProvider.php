<?php

declare(strict_types=1);

namespace Modules\Warehouse\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;
use Modules\Warehouse\Repositories\EloquentWarehouseLocationRepository;
use Modules\Warehouse\Repositories\EloquentWarehouseRepository;
use Modules\Warehouse\Repositories\WarehouseLocationRepositoryInterface;
use Modules\Warehouse\Repositories\WarehouseRepositoryInterface;

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
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
