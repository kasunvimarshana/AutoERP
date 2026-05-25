<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Pricing\Application\Contracts\UseCases\CustomerPriceLists\CreateCustomerPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\CustomerPriceLists\DeleteCustomerPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\CustomerPriceLists\GetCustomerPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\CustomerPriceLists\ListCustomerPriceListsServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\CustomerPriceLists\UpdateCustomerPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\PriceListItems\CreatePriceListItemServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\PriceListItems\DeletePriceListItemServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\PriceListItems\GetPriceListItemServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\PriceListItems\ListPriceListItemsServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\PriceListItems\UpdatePriceListItemServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\PriceLists\CreatePriceListServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\PriceLists\DeletePriceListServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\PriceLists\GetPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\PriceLists\ListPriceListsServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\PriceLists\UpdatePriceListServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\SupplierPriceLists\CreateSupplierPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\SupplierPriceLists\DeleteSupplierPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\SupplierPriceLists\GetSupplierPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\SupplierPriceLists\ListSupplierPriceListsServiceInterface;
use Modules\Pricing\Application\Contracts\UseCases\SupplierPriceLists\UpdateSupplierPriceListServiceInterface;
use Modules\Pricing\Application\Repositories\CustomerPriceListRepositoryInterface;
use Modules\Pricing\Application\Repositories\PriceListItemRepositoryInterface;
use Modules\Pricing\Application\Repositories\PriceListRepositoryInterface;
use Modules\Pricing\Application\Repositories\SupplierPriceListRepositoryInterface;
use Modules\Pricing\Application\UseCases\CustomerPriceLists\CreateCustomerPriceListService;
use Modules\Pricing\Application\UseCases\CustomerPriceLists\DeleteCustomerPriceListService;
use Modules\Pricing\Application\UseCases\CustomerPriceLists\GetCustomerPriceListService;
use Modules\Pricing\Application\UseCases\CustomerPriceLists\ListCustomerPriceListsService;
use Modules\Pricing\Application\UseCases\CustomerPriceLists\UpdateCustomerPriceListService;
use Modules\Pricing\Application\UseCases\PriceListItems\CreatePriceListItemService;
use Modules\Pricing\Application\UseCases\PriceListItems\DeletePriceListItemService;
use Modules\Pricing\Application\UseCases\PriceListItems\GetPriceListItemService;
use Modules\Pricing\Application\UseCases\PriceListItems\ListPriceListItemsService;
use Modules\Pricing\Application\UseCases\PriceListItems\UpdatePriceListItemService;
use Modules\Pricing\Application\UseCases\PriceLists\CreatePriceListService;
use Modules\Pricing\Application\UseCases\PriceLists\DeletePriceListService;
use Modules\Pricing\Application\UseCases\PriceLists\GetPriceListService;
use Modules\Pricing\Application\UseCases\PriceLists\ListPriceListsService;
use Modules\Pricing\Application\UseCases\PriceLists\UpdatePriceListService;
use Modules\Pricing\Application\UseCases\SupplierPriceLists\CreateSupplierPriceListService;
use Modules\Pricing\Application\UseCases\SupplierPriceLists\DeleteSupplierPriceListService;
use Modules\Pricing\Application\UseCases\SupplierPriceLists\GetSupplierPriceListService;
use Modules\Pricing\Application\UseCases\SupplierPriceLists\ListSupplierPriceListsService;
use Modules\Pricing\Application\UseCases\SupplierPriceLists\UpdateSupplierPriceListService;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\CustomerPriceListModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PriceListItemModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PriceListModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\SupplierPriceListModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories\EloquentCustomerPriceListRepository;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories\EloquentPriceListItemRepository;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories\EloquentPriceListRepository;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierPriceListRepository;

final class PricingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/pricing.php', 'pricing');

        foreach (
            [
                ListPriceListsServiceInterface::class => ListPriceListsService::class,
                GetPriceListServiceInterface::class => GetPriceListService::class,
                CreatePriceListServiceInterface::class => CreatePriceListService::class,
                UpdatePriceListServiceInterface::class => UpdatePriceListService::class,
                DeletePriceListServiceInterface::class => DeletePriceListService::class,
                ListPriceListItemsServiceInterface::class => ListPriceListItemsService::class,
                GetPriceListItemServiceInterface::class => GetPriceListItemService::class,
                CreatePriceListItemServiceInterface::class => CreatePriceListItemService::class,
                UpdatePriceListItemServiceInterface::class => UpdatePriceListItemService::class,
                DeletePriceListItemServiceInterface::class => DeletePriceListItemService::class,
                ListSupplierPriceListsServiceInterface::class => ListSupplierPriceListsService::class,
                GetSupplierPriceListServiceInterface::class => GetSupplierPriceListService::class,
                CreateSupplierPriceListServiceInterface::class => CreateSupplierPriceListService::class,
                UpdateSupplierPriceListServiceInterface::class => UpdateSupplierPriceListService::class,
                DeleteSupplierPriceListServiceInterface::class => DeleteSupplierPriceListService::class,
                ListCustomerPriceListsServiceInterface::class => ListCustomerPriceListsService::class,
                GetCustomerPriceListServiceInterface::class => GetCustomerPriceListService::class,
                CreateCustomerPriceListServiceInterface::class => CreateCustomerPriceListService::class,
                UpdateCustomerPriceListServiceInterface::class => UpdateCustomerPriceListService::class,
                DeleteCustomerPriceListServiceInterface::class => DeleteCustomerPriceListService::class,
            ] as $contract => $implementation
        ) {
            $this->app->singleton($contract, $implementation);
        }

        $this->app->singleton(PriceListRepositoryInterface::class, function (): PriceListRepositoryInterface {
            return new EloquentPriceListRepository(new PriceListModel());
        });
        $this->app->singleton(PriceListItemRepositoryInterface::class, function (): PriceListItemRepositoryInterface {
            return new EloquentPriceListItemRepository(new PriceListItemModel());
        });
        $this->app->singleton(SupplierPriceListRepositoryInterface::class, function (): SupplierPriceListRepositoryInterface {
            return new EloquentSupplierPriceListRepository(new SupplierPriceListModel());
        });
        $this->app->singleton(CustomerPriceListRepositoryInterface::class, function (): CustomerPriceListRepositoryInterface {
            return new EloquentCustomerPriceListRepository(new CustomerPriceListModel());
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../Persistence/Eloquent/Migrations');
    }
}