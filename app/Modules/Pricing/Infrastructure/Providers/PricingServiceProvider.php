<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\Pricing\Application\Contracts\CreateCustomerPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\CreatePriceListItemServiceInterface;
use Modules\Pricing\Application\Contracts\CreatePriceListServiceInterface;
use Modules\Pricing\Application\Contracts\CreateSupplierPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\DeleteCustomerPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\DeletePriceListItemServiceInterface;
use Modules\Pricing\Application\Contracts\DeletePriceListServiceInterface;
use Modules\Pricing\Application\Contracts\DeleteSupplierPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\FindCustomerPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\FindPriceListItemServiceInterface;
use Modules\Pricing\Application\Contracts\FindPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\FindSupplierPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\ResolvePriceServiceInterface;
use Modules\Pricing\Application\Contracts\UpdatePriceListItemServiceInterface;
use Modules\Pricing\Application\Contracts\UpdatePriceListServiceInterface;
use Modules\Pricing\Application\Services\CreateCustomerPriceListService;
use Modules\Pricing\Application\Services\CreatePriceListItemService;
use Modules\Pricing\Application\Services\CreatePriceListService;
use Modules\Pricing\Application\Services\CreateSupplierPriceListService;
use Modules\Pricing\Application\Services\DeleteCustomerPriceListService;
use Modules\Pricing\Application\Services\DeletePriceListItemService;
use Modules\Pricing\Application\Services\DeletePriceListService;
use Modules\Pricing\Application\Services\DeleteSupplierPriceListService;
use Modules\Pricing\Application\Services\FindCustomerPriceListService;
use Modules\Pricing\Application\Services\FindPriceListItemService;
use Modules\Pricing\Application\Services\FindPriceListService;
use Modules\Pricing\Application\Services\FindSupplierPriceListService;
use Modules\Pricing\Application\Services\ResolvePriceService;
use Modules\Pricing\Application\Services\UpdatePriceListItemService;
use Modules\Pricing\Application\Services\UpdatePriceListService;
use Modules\Pricing\Domain\RepositoryInterfaces\CustomerPriceListRepositoryInterface;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListItemRepositoryInterface;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListRepositoryInterface;
use Modules\Pricing\Domain\RepositoryInterfaces\SupplierPriceListRepositoryInterface;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories\EloquentCustomerPriceListRepository;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories\EloquentPriceListItemRepository;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories\EloquentPriceListRepository;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories\EloquentSupplierPriceListRepository;

class PricingServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $this->app->bind(PriceListRepositoryInterface::class, EloquentPriceListRepository::class);
        $this->app->bind(PriceListItemRepositoryInterface::class, EloquentPriceListItemRepository::class);
        $this->app->bind(CustomerPriceListRepositoryInterface::class, EloquentCustomerPriceListRepository::class);
        $this->app->bind(SupplierPriceListRepositoryInterface::class, EloquentSupplierPriceListRepository::class);

        $this->app->bind(CreatePriceListServiceInterface::class, CreatePriceListService::class);
        $this->app->bind(FindPriceListServiceInterface::class, FindPriceListService::class);
        $this->app->bind(UpdatePriceListServiceInterface::class, UpdatePriceListService::class);
        $this->app->bind(DeletePriceListServiceInterface::class, DeletePriceListService::class);

        $this->app->bind(CreatePriceListItemServiceInterface::class, CreatePriceListItemService::class);
        $this->app->bind(FindPriceListItemServiceInterface::class, FindPriceListItemService::class);
        $this->app->bind(UpdatePriceListItemServiceInterface::class, UpdatePriceListItemService::class);
        $this->app->bind(DeletePriceListItemServiceInterface::class, DeletePriceListItemService::class);

        $this->app->bind(CreateCustomerPriceListServiceInterface::class, CreateCustomerPriceListService::class);
        $this->app->bind(FindCustomerPriceListServiceInterface::class, FindCustomerPriceListService::class);
        $this->app->bind(DeleteCustomerPriceListServiceInterface::class, DeleteCustomerPriceListService::class);

        $this->app->bind(CreateSupplierPriceListServiceInterface::class, CreateSupplierPriceListService::class);
        $this->app->bind(FindSupplierPriceListServiceInterface::class, FindSupplierPriceListService::class);
        $this->app->bind(DeleteSupplierPriceListServiceInterface::class, DeleteSupplierPriceListService::class);

        $this->app->bind(ResolvePriceServiceInterface::class, ResolvePriceService::class);
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__.'/../../routes/api.php',
            __DIR__.'/../../database/migrations',
        );
    }
}
