<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Warehouse\Application\Contracts\CreateWarehouseServiceInterface;
use Modules\Warehouse\Application\Contracts\CreateWarehouseZoneServiceInterface;
use Modules\Warehouse\Application\Contracts\DeleteWarehouseServiceInterface;
use Modules\Warehouse\Application\Contracts\DeleteWarehouseZoneServiceInterface;
use Modules\Warehouse\Application\Contracts\FindWarehouseServiceInterface;
use Modules\Warehouse\Application\Contracts\FindWarehouseZoneServiceInterface;
use Modules\Warehouse\Application\Contracts\UpdateWarehouseServiceInterface;
use Modules\Warehouse\Application\Contracts\UpdateWarehouseZoneServiceInterface;
use Modules\Warehouse\Application\Services\CreateWarehouseService;
use Modules\Warehouse\Application\Services\CreateWarehouseZoneService;
use Modules\Warehouse\Application\Services\DeleteWarehouseService;
use Modules\Warehouse\Application\Services\DeleteWarehouseZoneService;
use Modules\Warehouse\Application\Services\FindWarehouseService;
use Modules\Warehouse\Application\Services\FindWarehouseZoneService;
use Modules\Warehouse\Application\Services\UpdateWarehouseService;
use Modules\Warehouse\Application\Services\UpdateWarehouseZoneService;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseRepositoryInterface;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseZoneRepositoryInterface;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseZoneModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories\EloquentWarehouseRepository;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories\EloquentWarehouseZoneRepository;

class WarehouseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WarehouseRepositoryInterface::class, function ($app) {
            return new EloquentWarehouseRepository($app->make(WarehouseModel::class));
        });

        $this->app->bind(WarehouseZoneRepositoryInterface::class, function ($app) {
            return new EloquentWarehouseZoneRepository($app->make(WarehouseZoneModel::class));
        });

        $this->app->bind(CreateWarehouseServiceInterface::class, function ($app) {
            return new CreateWarehouseService($app->make(WarehouseRepositoryInterface::class));
        });

        $this->app->bind(FindWarehouseServiceInterface::class, function ($app) {
            return new FindWarehouseService($app->make(WarehouseRepositoryInterface::class));
        });

        $this->app->bind(UpdateWarehouseServiceInterface::class, function ($app) {
            return new UpdateWarehouseService($app->make(WarehouseRepositoryInterface::class));
        });

        $this->app->bind(DeleteWarehouseServiceInterface::class, function ($app) {
            return new DeleteWarehouseService($app->make(WarehouseRepositoryInterface::class));
        });

        $this->app->bind(CreateWarehouseZoneServiceInterface::class, function ($app) {
            return new CreateWarehouseZoneService($app->make(WarehouseZoneRepositoryInterface::class));
        });

        $this->app->bind(FindWarehouseZoneServiceInterface::class, function ($app) {
            return new FindWarehouseZoneService($app->make(WarehouseZoneRepositoryInterface::class));
        });

        $this->app->bind(UpdateWarehouseZoneServiceInterface::class, function ($app) {
            return new UpdateWarehouseZoneService($app->make(WarehouseZoneRepositoryInterface::class));
        });

        $this->app->bind(DeleteWarehouseZoneServiceInterface::class, function ($app) {
            return new DeleteWarehouseZoneService($app->make(WarehouseZoneRepositoryInterface::class));
        });
    }

    public function boot(): void
    {
        Route::middleware('api')
             ->prefix('api')
             ->group(function () {
                 $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
             });

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
