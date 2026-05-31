<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Pricing\Application\Contracts\Services\DiscountServiceInterface;
use Modules\Pricing\Application\Contracts\Services\PriceListServiceInterface;
use Modules\Pricing\Application\Contracts\Services\PriceResolverServiceInterface;
use Modules\Pricing\Application\Contracts\Services\PriceValidationServiceInterface;
use Modules\Pricing\Application\Contracts\Services\PricingRuleServiceInterface;
use Modules\Pricing\Application\Contracts\Services\PricingUsageSummaryServiceInterface;
use Modules\Pricing\Application\Contracts\Services\TierPricingServiceInterface;
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
use Modules\Pricing\Application\Repositories\DiscountRepositoryInterface;
use Modules\Pricing\Application\Repositories\DiscountRuleRepositoryInterface;
use Modules\Pricing\Application\Repositories\PriceHistoryRepositoryInterface;
use Modules\Pricing\Application\Repositories\PriceListItemRepositoryInterface;
use Modules\Pricing\Application\Repositories\PriceListRepositoryInterface;
use Modules\Pricing\Application\Repositories\PricingRuleConditionRepositoryInterface;
use Modules\Pricing\Application\Repositories\PricingRuleRepositoryInterface;
use Modules\Pricing\Application\Repositories\PricingTierRepositoryInterface;
use Modules\Pricing\Application\Repositories\SupplierPriceListRepositoryInterface;
use Modules\Pricing\Application\Services\DiscountService;
use Modules\Pricing\Application\Services\PriceListService;
use Modules\Pricing\Application\Services\PriceResolverService;
use Modules\Pricing\Application\Services\PriceValidationService;
use Modules\Pricing\Application\Services\PricingRuleService;
use Modules\Pricing\Application\Services\TierPricingService;
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
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\DiscountModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\DiscountRuleModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PriceHistoryModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PriceListItemModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PriceListModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PricingRuleConditionModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PricingRuleModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PricingTierModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\SupplierPriceListModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories\EloquentCustomerPriceListRepository;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories\EloquentDiscountRepository;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories\EloquentDiscountRuleRepository;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories\EloquentPriceHistoryRepository;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories\EloquentPriceListItemRepository;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories\EloquentPriceListRepository;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories\EloquentPricingRuleConditionRepository;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories\EloquentPricingRuleRepository;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories\EloquentPricingTierRepository;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierPriceListRepository;
use Modules\Pricing\Infrastructure\Services\DatabasePricingUsageSummaryService;

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

        $this->app->singleton(PriceValidationServiceInterface::class, PriceValidationService::class);
        $this->app->singleton(TierPricingServiceInterface::class, TierPricingService::class);
        $this->app->singleton(DiscountServiceInterface::class, DiscountService::class);
        $this->app->singleton(PricingRuleServiceInterface::class, PricingRuleService::class);
        $this->app->singleton(PriceListServiceInterface::class, PriceListService::class);
        $this->app->singleton(PriceResolverServiceInterface::class, PriceResolverService::class);
        $this->app->singleton(PricingUsageSummaryServiceInterface::class, DatabasePricingUsageSummaryService::class);

        $this->app->singleton(PriceListRepositoryInterface::class, function (): PriceListRepositoryInterface {
            return new EloquentPriceListRepository(new PriceListModel());
        });
        $this->app->singleton(PriceListItemRepositoryInterface::class, function (): PriceListItemRepositoryInterface {
            return new EloquentPriceListItemRepository(new PriceListItemModel());
        });
        $this->app->singleton(PricingTierRepositoryInterface::class, function (): PricingTierRepositoryInterface {
            return new EloquentPricingTierRepository(new PricingTierModel());
        });
        $this->app->singleton(PricingRuleRepositoryInterface::class, function (): PricingRuleRepositoryInterface {
            return new EloquentPricingRuleRepository(new PricingRuleModel());
        });
        $this->app->singleton(PricingRuleConditionRepositoryInterface::class, function (): PricingRuleConditionRepositoryInterface {
            return new EloquentPricingRuleConditionRepository(new PricingRuleConditionModel());
        });
        $this->app->singleton(DiscountRepositoryInterface::class, function (): DiscountRepositoryInterface {
            return new EloquentDiscountRepository(new DiscountModel());
        });
        $this->app->singleton(DiscountRuleRepositoryInterface::class, function (): DiscountRuleRepositoryInterface {
            return new EloquentDiscountRuleRepository(new DiscountRuleModel());
        });
        $this->app->singleton(PriceHistoryRepositoryInterface::class, function (): PriceHistoryRepositoryInterface {
            return new EloquentPriceHistoryRepository(new PriceHistoryModel());
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
