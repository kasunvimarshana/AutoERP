<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Warehouse\Application\Contracts\OrganizationUnitServiceInterface;
use Modules\Warehouse\Application\Contracts\WarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Contracts\WarehouseServiceInterface;
use Modules\Warehouse\Application\Services\OrganizationUnitService;
use Modules\Warehouse\Application\Services\WarehouseLocationService;
use Modules\Warehouse\Application\Services\WarehouseService;
use Modules\Warehouse\Domain\Contracts\Repositories\OrganizationUnitRepositoryInterface;
use Modules\Warehouse\Domain\Contracts\Repositories\WarehouseLocationRepositoryInterface;
use Modules\Warehouse\Domain\Contracts\Repositories\WarehouseRepositoryInterface;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseLocationModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrganizationUnitRepository;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories\EloquentWarehouseLocationRepository;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories\EloquentWarehouseRepository;

class WarehouseServiceProvider extends ServiceProvider
{
    /**
     * Register Warehouse module bindings.
     */
    public function register(): void
    {
        // Repositories
        $this->app->bind(WarehouseRepositoryInterface::class, function ($app) {
            return new EloquentWarehouseRepository($app->make(WarehouseModel::class));
        });

        $this->app->bind(WarehouseLocationRepositoryInterface::class, function ($app) {
            return new EloquentWarehouseLocationRepository($app->make(WarehouseLocationModel::class));
        });

        $this->app->bind(OrganizationUnitRepositoryInterface::class, function ($app) {
            return new EloquentOrganizationUnitRepository($app->make(OrganizationUnitModel::class));
        });

        // Services
        $this->app->bind(WarehouseServiceInterface::class, function ($app) {
            return new WarehouseService($app->make(WarehouseRepositoryInterface::class));
        });

        $this->app->bind(WarehouseLocationServiceInterface::class, function ($app) {
            return new WarehouseLocationService($app->make(WarehouseLocationRepositoryInterface::class));
        });

        $this->app->bind(OrganizationUnitServiceInterface::class, function ($app) {
            return new OrganizationUnitService($app->make(OrganizationUnitRepositoryInterface::class));
        });
    }

    /**
     * Boot the Warehouse service provider.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
