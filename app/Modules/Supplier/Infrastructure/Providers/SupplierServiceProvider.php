<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Supplier\Application\Contracts\Services\SupplierManagementServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierAddresses\CreateSupplierAddressServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierAddresses\DeleteSupplierAddressServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierAddresses\GetSupplierAddressServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierAddresses\ListSupplierAddressesServiceInterface;
use Modules\Supplier\Application\Contracts\UseCases\SupplierAddresses\UpdateSupplierAddressServiceInterface;
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
use Modules\Supplier\Application\Repositories\SupplierAddressRepositoryInterface;
use Modules\Supplier\Application\Repositories\SupplierBankAccountRepositoryInterface;
use Modules\Supplier\Application\Repositories\SupplierCategoryRepositoryInterface;
use Modules\Supplier\Application\Repositories\SupplierContactRepositoryInterface;
use Modules\Supplier\Application\Repositories\SupplierItemRepositoryInterface;
use Modules\Supplier\Application\Repositories\SupplierRepositoryInterface;
use Modules\Supplier\Application\Repositories\SupplierStatusHistoryRepositoryInterface;
use Modules\Supplier\Application\Repositories\SupplierTaxProfileRepositoryInterface;
use Modules\Supplier\Application\Repositories\SupplierUserAccountRepositoryInterface;
use Modules\Supplier\Application\Repositories\SupplierVehicleRepositoryInterface;
use Modules\Supplier\Application\UseCases\SupplierAddresses\CreateSupplierAddressService;
use Modules\Supplier\Application\UseCases\SupplierAddresses\DeleteSupplierAddressService;
use Modules\Supplier\Application\UseCases\SupplierAddresses\GetSupplierAddressService;
use Modules\Supplier\Application\UseCases\SupplierAddresses\ListSupplierAddressesService;
use Modules\Supplier\Application\UseCases\SupplierAddresses\UpdateSupplierAddressService;
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
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierAddressModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierBankAccountModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierCategoryModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierContactModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierItemModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierStatusHistoryModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierTaxProfileModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierUserAccountModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierVehicleModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierAddressRepository;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierBankAccountRepository;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierCategoryRepository;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierContactRepository;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierItemRepository;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierRepository;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierStatusHistoryRepository;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierTaxProfileRepository;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierUserAccountRepository;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierVehicleRepository;
use Modules\Supplier\Infrastructure\Services\SupplierManagementService;

final class SupplierServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/supplier.php', 'supplier');

        foreach (
            [
                SupplierManagementServiceInterface::class => SupplierManagementService::class,
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
                GetSupplierAddressServiceInterface::class => GetSupplierAddressService::class,
                CreateSupplierAddressServiceInterface::class => CreateSupplierAddressService::class,
                UpdateSupplierAddressServiceInterface::class => UpdateSupplierAddressService::class,
                DeleteSupplierAddressServiceInterface::class => DeleteSupplierAddressService::class,
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
            return new EloquentSupplierRepository(new SupplierModel);
        });
        $this->app->singleton(
            SupplierContactRepositoryInterface::class,
            function (): SupplierContactRepositoryInterface {
                return new EloquentSupplierContactRepository(new SupplierContactModel);
            },
        );
        $this->app->singleton(
            SupplierAddressRepositoryInterface::class,
            function (): SupplierAddressRepositoryInterface {
                return new EloquentSupplierAddressRepository(new SupplierAddressModel);
            },
        );
        $this->app->singleton(
            SupplierVehicleRepositoryInterface::class,
            function (): SupplierVehicleRepositoryInterface {
                return new EloquentSupplierVehicleRepository(new SupplierVehicleModel);
            },
        );
        $this->app->singleton(SupplierItemRepositoryInterface::class, function (): SupplierItemRepositoryInterface {
            return new EloquentSupplierItemRepository(new SupplierItemModel);
        });
        $this->app->singleton(
            SupplierCategoryRepositoryInterface::class,
            function (): SupplierCategoryRepositoryInterface {
                return new EloquentSupplierCategoryRepository(new SupplierCategoryModel);
            },
        );
        $this->app->singleton(
            SupplierBankAccountRepositoryInterface::class,
            function (): SupplierBankAccountRepositoryInterface {
                return new EloquentSupplierBankAccountRepository(new SupplierBankAccountModel);
            },
        );
        $this->app->singleton(
            SupplierTaxProfileRepositoryInterface::class,
            function (): SupplierTaxProfileRepositoryInterface {
                return new EloquentSupplierTaxProfileRepository(new SupplierTaxProfileModel);
            },
        );
        $this->app->singleton(
            SupplierUserAccountRepositoryInterface::class,
            function (): SupplierUserAccountRepositoryInterface {
                return new EloquentSupplierUserAccountRepository(new SupplierUserAccountModel);
            },
        );
        $this->app->singleton(
            SupplierStatusHistoryRepositoryInterface::class,
            function (): SupplierStatusHistoryRepositoryInterface {
                return new EloquentSupplierStatusHistoryRepository(new SupplierStatusHistoryModel);
            },
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Eloquent/Migrations');
    }
}
