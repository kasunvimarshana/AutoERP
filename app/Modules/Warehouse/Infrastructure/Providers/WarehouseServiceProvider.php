<?php
namespace Modules\Warehouse\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Warehouse\Application\Contracts\CreateWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Contracts\CreateWarehouseServiceInterface;
use Modules\Warehouse\Application\Contracts\CreateWarehouseZoneServiceInterface;
use Modules\Warehouse\Application\Contracts\DeleteWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Contracts\DeleteWarehouseServiceInterface;
use Modules\Warehouse\Application\Contracts\DeleteWarehouseZoneServiceInterface;
use Modules\Warehouse\Application\Contracts\UpdateWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Contracts\UpdateWarehouseServiceInterface;
use Modules\Warehouse\Application\Contracts\UpdateWarehouseZoneServiceInterface;
use Modules\Warehouse\Application\Services\CreateWarehouseLocationService;
use Modules\Warehouse\Application\Services\CreateWarehouseService;
use Modules\Warehouse\Application\Services\CreateWarehouseZoneService;
use Modules\Warehouse\Application\Services\DeleteWarehouseLocationService;
use Modules\Warehouse\Application\Services\DeleteWarehouseService;
use Modules\Warehouse\Application\Services\DeleteWarehouseZoneService;
use Modules\Warehouse\Application\Services\UpdateWarehouseLocationService;
use Modules\Warehouse\Application\Services\UpdateWarehouseService;
use Modules\Warehouse\Application\Services\UpdateWarehouseZoneService;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseLocationRepositoryInterface;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseRepositoryInterface;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseZoneRepositoryInterface;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories\EloquentWarehouseLocationRepository;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories\EloquentWarehouseRepository;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories\EloquentWarehouseZoneRepository;

class WarehouseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WarehouseRepositoryInterface::class, EloquentWarehouseRepository::class);
        $this->app->bind(WarehouseZoneRepositoryInterface::class, EloquentWarehouseZoneRepository::class);
        $this->app->bind(WarehouseLocationRepositoryInterface::class, EloquentWarehouseLocationRepository::class);

        $this->app->bind(CreateWarehouseServiceInterface::class, CreateWarehouseService::class);
        $this->app->bind(UpdateWarehouseServiceInterface::class, UpdateWarehouseService::class);
        $this->app->bind(DeleteWarehouseServiceInterface::class, DeleteWarehouseService::class);
        $this->app->bind(CreateWarehouseZoneServiceInterface::class, CreateWarehouseZoneService::class);
        $this->app->bind(UpdateWarehouseZoneServiceInterface::class, UpdateWarehouseZoneService::class);
        $this->app->bind(DeleteWarehouseZoneServiceInterface::class, DeleteWarehouseZoneService::class);
        $this->app->bind(CreateWarehouseLocationServiceInterface::class, CreateWarehouseLocationService::class);
        $this->app->bind(UpdateWarehouseLocationServiceInterface::class, UpdateWarehouseLocationService::class);
        $this->app->bind(DeleteWarehouseLocationServiceInterface::class, DeleteWarehouseLocationService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
