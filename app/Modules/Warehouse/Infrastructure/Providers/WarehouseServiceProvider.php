<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Warehouse\Application\Contracts\UseCases\WarehouseLocations\CreateWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Contracts\UseCases\WarehouseLocations\DeleteWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Contracts\UseCases\WarehouseLocations\GetWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Contracts\UseCases\WarehouseLocations\ListWarehouseLocationsServiceInterface;
use Modules\Warehouse\Application\Contracts\UseCases\WarehouseLocations\UpdateWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Contracts\UseCases\Warehouses\CreateWarehouseServiceInterface;
use Modules\Warehouse\Application\Contracts\UseCases\Warehouses\DeleteWarehouseServiceInterface;
use Modules\Warehouse\Application\Contracts\UseCases\Warehouses\GetWarehouseServiceInterface;
use Modules\Warehouse\Application\Contracts\UseCases\Warehouses\ListWarehousesServiceInterface;
use Modules\Warehouse\Application\Contracts\UseCases\Warehouses\UpdateWarehouseServiceInterface;
use Modules\Warehouse\Application\Repositories\WarehouseLocationRepositoryInterface;
use Modules\Warehouse\Application\Repositories\WarehouseRepositoryInterface;
use Modules\Warehouse\Application\UseCases\WarehouseLocations\CreateWarehouseLocationService;
use Modules\Warehouse\Application\UseCases\WarehouseLocations\DeleteWarehouseLocationService;
use Modules\Warehouse\Application\UseCases\WarehouseLocations\GetWarehouseLocationService;
use Modules\Warehouse\Application\UseCases\WarehouseLocations\ListWarehouseLocationsService;
use Modules\Warehouse\Application\UseCases\WarehouseLocations\UpdateWarehouseLocationService;
use Modules\Warehouse\Application\UseCases\Warehouses\CreateWarehouseService;
use Modules\Warehouse\Application\UseCases\Warehouses\DeleteWarehouseService;
use Modules\Warehouse\Application\UseCases\Warehouses\GetWarehouseService;
use Modules\Warehouse\Application\UseCases\Warehouses\ListWarehousesService;
use Modules\Warehouse\Application\UseCases\Warehouses\UpdateWarehouseService;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseLocationModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories\EloquentWarehouseLocationRepository;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories\EloquentWarehouseRepository;

final class WarehouseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/warehouse.php', 'warehouse');

        foreach (
            [
                ListWarehousesServiceInterface::class => ListWarehousesService::class,
                GetWarehouseServiceInterface::class => GetWarehouseService::class,
                CreateWarehouseServiceInterface::class => CreateWarehouseService::class,
                UpdateWarehouseServiceInterface::class => UpdateWarehouseService::class,
                DeleteWarehouseServiceInterface::class => DeleteWarehouseService::class,
                ListWarehouseLocationsServiceInterface::class => ListWarehouseLocationsService::class,
                GetWarehouseLocationServiceInterface::class => GetWarehouseLocationService::class,
                CreateWarehouseLocationServiceInterface::class => CreateWarehouseLocationService::class,
                UpdateWarehouseLocationServiceInterface::class => UpdateWarehouseLocationService::class,
                DeleteWarehouseLocationServiceInterface::class => DeleteWarehouseLocationService::class,
            ] as $contract => $implementation
        ) {
            $this->app->singleton($contract, $implementation);
        }

        $this->app->singleton(WarehouseRepositoryInterface::class, function (): WarehouseRepositoryInterface {
            return new EloquentWarehouseRepository(new WarehouseModel());
        });
        $this->app->singleton(WarehouseLocationRepositoryInterface::class, function (): WarehouseLocationRepositoryInterface {
            return new EloquentWarehouseLocationRepository(new WarehouseLocationModel());
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../Persistence/Eloquent/Migrations');
    }
}