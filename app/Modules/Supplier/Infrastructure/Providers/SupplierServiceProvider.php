<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Supplier\Application\Contracts\UseCases\SupplierAddresses\CreateSupplierAddressesServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierAddresses\DeleteSupplierAddressesServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierAddresses\GetSupplierAddressesServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierAddresses\ListSupplierAddressesServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierAddresses\UpdateSupplierAddressesServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierContacts\CreateSupplierContactServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierContacts\DeleteSupplierContactServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierContacts\GetSupplierContactServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierContacts\ListSupplierContactsServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierContacts\UpdateSupplierContactServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierItems\CreateSupplierItemServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierItems\DeleteSupplierItemServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierItems\GetSupplierItemServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierItems\ListSupplierItemsServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierItems\UpdateSupplierItemServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\Suppliers\CreateSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\Suppliers\DeleteSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\Suppliers\GetSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\Suppliers\ListSuppliersServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\Suppliers\UpdateSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierVehicles\CreateSupplierVehicleServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierVehicles\DeleteSupplierVehicleServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierVehicles\GetSupplierVehicleServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierVehicles\ListSupplierVehiclesServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierVehicles\UpdateSupplierVehicleServiceInterface;
use Modules\Supplier\Application\Repositories\SupplierAddressesRepositoryInterface;
use Modules\Supplier\Application\Repositories\SupplierContactRepositoryInterface;
use Modules\Supplier\Application\Repositories\SupplierItemRepositoryInterface;
use Modules\Supplier\Application\Repositories\SupplierRepositoryInterface;
use Modules\Supplier\Application\Repositories\SupplierVehicleRepositoryInterface;
use Modules\Supplier\Application\UseCases\SupplierAddresses\CreateSupplierAddressesService;
use Modules\Supplier\Application\UseCases\SupplierAddresses\DeleteSupplierAddressesService;
use Modules\Supplier\Application\UseCases\SupplierAddresses\GetSupplierAddressesService;
use Modules\Supplier\Application\UseCases\SupplierAddresses\ListSupplierAddressesService;
use Modules\Supplier\Application\UseCases\SupplierAddresses\UpdateSupplierAddressesService;
use Modules\Supplier\Application\UseCases\SupplierContacts\CreateSupplierContactService;
use Modules\Supplier\Application\UseCases\SupplierContacts\DeleteSupplierContactService;
use Modules\Supplier\Application\UseCases\SupplierContacts\GetSupplierContactService;
use Modules\Supplier\Application\UseCases\SupplierContacts\ListSupplierContactsService;
use Modules\Supplier\Application\UseCases\SupplierContacts\UpdateSupplierContactService;
use Modules\Supplier\Application\UseCases\SupplierItems\CreateSupplierItemService;
use Modules\Supplier\Application\UseCases\SupplierItems\DeleteSupplierItemService;
use Modules\Supplier\Application\UseCases\SupplierItems\GetSupplierItemService;
use Modules\Supplier\Application\UseCases\SupplierItems\ListSupplierItemsService;
use Modules\Supplier\Application\UseCases\SupplierItems\UpdateSupplierItemService;
use Modules\Supplier\Application\UseCases\Suppliers\CreateSupplierService;
use Modules\Supplier\Application\UseCases\Suppliers\DeleteSupplierService;
use Modules\Supplier\Application\UseCases\Suppliers\GetSupplierService;
use Modules\Supplier\Application\UseCases\Suppliers\ListSuppliersService;
use Modules\Supplier\Application\UseCases\Suppliers\UpdateSupplierService;
use Modules\Supplier\Application\UseCases\SupplierVehicles\CreateSupplierVehicleService;
use Modules\Supplier\Application\UseCases\SupplierVehicles\DeleteSupplierVehicleService;
use Modules\Supplier\Application\UseCases\SupplierVehicles\GetSupplierVehicleService;
use Modules\Supplier\Application\UseCases\SupplierVehicles\ListSupplierVehiclesService;
use Modules\Supplier\Application\UseCases\SupplierVehicles\UpdateSupplierVehicleService;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierAddressesModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierContactModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierItemModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierVehicleModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierAddressesRepository;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierContactRepository;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierItemRepository;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierRepository;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierVehicleRepository;

final class SupplierServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/supplier.php', 'supplier');

        foreach (
            [
                ListSuppliersServiceInterface::class => ListSuppliersService::class,
                GetSupplierServiceInterface::class => GetSupplierService::class,
                CreateSupplierServiceInterface::class => CreateSupplierService::class,
                UpdateSupplierServiceInterface::class => UpdateSupplierService::class,
                DeleteSupplierServiceInterface::class => DeleteSupplierService::class,
                ListSupplierContactsServiceInterface::class => ListSupplierContactsService::class,
                GetSupplierContactServiceInterface::class => GetSupplierContactService::class,
                CreateSupplierContactServiceInterface::class => CreateSupplierContactService::class,
                UpdateSupplierContactServiceInterface::class => UpdateSupplierContactService::class,
                DeleteSupplierContactServiceInterface::class => DeleteSupplierContactService::class,
                ListSupplierAddressesServiceInterface::class => ListSupplierAddressesService::class,
                GetSupplierAddressesServiceInterface::class => GetSupplierAddressesService::class,
                CreateSupplierAddressesServiceInterface::class => CreateSupplierAddressesService::class,
                UpdateSupplierAddressesServiceInterface::class => UpdateSupplierAddressesService::class,
                DeleteSupplierAddressesServiceInterface::class => DeleteSupplierAddressesService::class,
                ListSupplierVehiclesServiceInterface::class => ListSupplierVehiclesService::class,
                GetSupplierVehicleServiceInterface::class => GetSupplierVehicleService::class,
                CreateSupplierVehicleServiceInterface::class => CreateSupplierVehicleService::class,
                UpdateSupplierVehicleServiceInterface::class => UpdateSupplierVehicleService::class,
                DeleteSupplierVehicleServiceInterface::class => DeleteSupplierVehicleService::class,
                ListSupplierItemsServiceInterface::class => ListSupplierItemsService::class,
                GetSupplierItemServiceInterface::class => GetSupplierItemService::class,
                CreateSupplierItemServiceInterface::class => CreateSupplierItemService::class,
                UpdateSupplierItemServiceInterface::class => UpdateSupplierItemService::class,
                DeleteSupplierItemServiceInterface::class => DeleteSupplierItemService::class,
            ] as $contract => $implementation
        ) {
            $this->app->singleton($contract, $implementation);
        }

        $this->app->singleton(SupplierRepositoryInterface::class, function (): SupplierRepositoryInterface {
            return new EloquentSupplierRepository(new SupplierModel());
        });
        $this->app->singleton(SupplierContactRepositoryInterface::class, function (): SupplierContactRepositoryInterface {
            return new EloquentSupplierContactRepository(new SupplierContactModel());
        });
        $this->app->singleton(SupplierAddressesRepositoryInterface::class, function (): SupplierAddressesRepositoryInterface {
            return new EloquentSupplierAddressesRepository(new SupplierAddressesModel());
        });
        $this->app->singleton(SupplierVehicleRepositoryInterface::class, function (): SupplierVehicleRepositoryInterface {
            return new EloquentSupplierVehicleRepository(new SupplierVehicleModel());
        });
        $this->app->singleton(SupplierItemRepositoryInterface::class, function (): SupplierItemRepositoryInterface {
            return new EloquentSupplierItemRepository(new SupplierItemModel());
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../Persistence/Eloquent/Migrations');
    }
}