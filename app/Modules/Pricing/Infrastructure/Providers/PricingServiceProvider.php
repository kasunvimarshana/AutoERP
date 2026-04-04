<?php
namespace Modules\Pricing\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Pricing\Application\Contracts\CreatePriceListItemServiceInterface;
use Modules\Pricing\Application\Contracts\CreatePriceListServiceInterface;
use Modules\Pricing\Application\Contracts\CreateTaxGroupServiceInterface;
use Modules\Pricing\Application\Contracts\CreateTaxRateServiceInterface;
use Modules\Pricing\Application\Contracts\DeletePriceListItemServiceInterface;
use Modules\Pricing\Application\Contracts\DeletePriceListServiceInterface;
use Modules\Pricing\Application\Contracts\UpdatePriceListItemServiceInterface;
use Modules\Pricing\Application\Contracts\UpdatePriceListServiceInterface;
use Modules\Pricing\Application\Services\CreatePriceListItemService;
use Modules\Pricing\Application\Services\CreatePriceListService;
use Modules\Pricing\Application\Services\CreateTaxGroupService;
use Modules\Pricing\Application\Services\CreateTaxRateService;
use Modules\Pricing\Application\Services\DeletePriceListItemService;
use Modules\Pricing\Application\Services\DeletePriceListService;
use Modules\Pricing\Application\Services\UpdatePriceListItemService;
use Modules\Pricing\Application\Services\UpdatePriceListService;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListItemRepositoryInterface;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListRepositoryInterface;
use Modules\Pricing\Domain\RepositoryInterfaces\TaxGroupRepositoryInterface;
use Modules\Pricing\Domain\RepositoryInterfaces\TaxRateRepositoryInterface;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories\EloquentPriceListItemRepository;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories\EloquentPriceListRepository;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories\EloquentTaxGroupRepository;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories\EloquentTaxRateRepository;

class PricingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PriceListRepositoryInterface::class, EloquentPriceListRepository::class);
        $this->app->bind(PriceListItemRepositoryInterface::class, EloquentPriceListItemRepository::class);
        $this->app->bind(TaxRateRepositoryInterface::class, EloquentTaxRateRepository::class);
        $this->app->bind(TaxGroupRepositoryInterface::class, EloquentTaxGroupRepository::class);
        $this->app->bind(CreatePriceListServiceInterface::class, CreatePriceListService::class);
        $this->app->bind(UpdatePriceListServiceInterface::class, UpdatePriceListService::class);
        $this->app->bind(DeletePriceListServiceInterface::class, DeletePriceListService::class);
        $this->app->bind(CreatePriceListItemServiceInterface::class, CreatePriceListItemService::class);
        $this->app->bind(UpdatePriceListItemServiceInterface::class, UpdatePriceListItemService::class);
        $this->app->bind(DeletePriceListItemServiceInterface::class, DeletePriceListItemService::class);
        $this->app->bind(CreateTaxRateServiceInterface::class, CreateTaxRateService::class);
        $this->app->bind(CreateTaxGroupServiceInterface::class, CreateTaxGroupService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
