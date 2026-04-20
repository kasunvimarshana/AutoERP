<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\Warehouse\Application\Contracts\CreateWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Contracts\CreateWarehouseServiceInterface;
use Modules\Warehouse\Application\Contracts\DeleteWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Contracts\DeleteWarehouseServiceInterface;
use Modules\Warehouse\Application\Contracts\FindWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Contracts\FindWarehouseServiceInterface;
use Modules\Warehouse\Application\Contracts\UpdateWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Contracts\UpdateWarehouseServiceInterface;
use Modules\Warehouse\Application\Services\CreateWarehouseLocationService;
use Modules\Warehouse\Application\Services\CreateWarehouseService;
use Modules\Warehouse\Application\Services\DeleteWarehouseLocationService;
use Modules\Warehouse\Application\Services\DeleteWarehouseService;
use Modules\Warehouse\Application\Services\FindWarehouseLocationService;
use Modules\Warehouse\Application\Services\FindWarehouseService;
use Modules\Warehouse\Application\Services\UpdateWarehouseLocationService;
use Modules\Warehouse\Application\Services\UpdateWarehouseService;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseLocationRepositoryInterface;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseRepositoryInterface;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories\EloquentWarehouseLocationRepository;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories\EloquentWarehouseRepository;

class WarehouseServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $this->app->bind(WarehouseRepositoryInterface::class, EloquentWarehouseRepository::class);
        $this->app->bind(WarehouseLocationRepositoryInterface::class, EloquentWarehouseLocationRepository::class);

        $this->app->bind(CreateWarehouseServiceInterface::class, CreateWarehouseService::class);
        $this->app->bind(FindWarehouseServiceInterface::class, FindWarehouseService::class);
        $this->app->bind(UpdateWarehouseServiceInterface::class, UpdateWarehouseService::class);
        $this->app->bind(DeleteWarehouseServiceInterface::class, DeleteWarehouseService::class);

        $this->app->bind(CreateWarehouseLocationServiceInterface::class, CreateWarehouseLocationService::class);
        $this->app->bind(FindWarehouseLocationServiceInterface::class, FindWarehouseLocationService::class);
        $this->app->bind(UpdateWarehouseLocationServiceInterface::class, UpdateWarehouseLocationService::class);
        $this->app->bind(DeleteWarehouseLocationServiceInterface::class, DeleteWarehouseLocationService::class);
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__.'/../../routes/api.php',
            __DIR__.'/../../database/migrations',
        );
    }
}
