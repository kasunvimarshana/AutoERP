<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Supplier\Application\Repositories\SupplierAddressRepositoryInterface;
use Modules\Supplier\Application\Repositories\SupplierContactRepositoryInterface;
use Modules\Supplier\Application\Repositories\SupplierItemRepositoryInterface;
use Modules\Supplier\Application\Repositories\SupplierRepositoryInterface;
use Modules\Supplier\Application\Repositories\SupplierVehicleRepositoryInterface;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierAddressRepository;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierContactRepository;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierItemRepository;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierRepository;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierVehicleRepository;

class SupplierServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/supplier.php', 'supplier');

        foreach ([
            SupplierAddressRepositoryInterface::class => EloquentSupplierAddressRepository::class,
            SupplierContactRepositoryInterface::class => EloquentSupplierContactRepository::class,
            SupplierItemRepositoryInterface::class => EloquentSupplierItemRepository::class,
            SupplierRepositoryInterface::class => EloquentSupplierRepository::class,
            SupplierVehicleRepositoryInterface::class => EloquentSupplierVehicleRepository::class,
        ] as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../Infrastructure/Persistence/Eloquent/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
