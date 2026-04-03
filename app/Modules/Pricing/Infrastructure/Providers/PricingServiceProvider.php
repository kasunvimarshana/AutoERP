<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Pricing\Application\Contracts\ActivatePriceListServiceInterface;
use Modules\Pricing\Application\Contracts\CreatePriceListItemServiceInterface;
use Modules\Pricing\Application\Contracts\CreatePriceListServiceInterface;
use Modules\Pricing\Application\Contracts\DeactivatePriceListServiceInterface;
use Modules\Pricing\Application\Contracts\DeletePriceListItemServiceInterface;
use Modules\Pricing\Application\Contracts\DeletePriceListServiceInterface;
use Modules\Pricing\Application\Contracts\FindPriceListItemServiceInterface;
use Modules\Pricing\Application\Contracts\FindPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\UpdatePriceListItemServiceInterface;
use Modules\Pricing\Application\Contracts\UpdatePriceListServiceInterface;
use Modules\Pricing\Application\Services\ActivatePriceListService;
use Modules\Pricing\Application\Services\CreatePriceListItemService;
use Modules\Pricing\Application\Services\CreatePriceListService;
use Modules\Pricing\Application\Services\DeactivatePriceListService;
use Modules\Pricing\Application\Services\DeletePriceListItemService;
use Modules\Pricing\Application\Services\DeletePriceListService;
use Modules\Pricing\Application\Services\FindPriceListItemService;
use Modules\Pricing\Application\Services\FindPriceListService;
use Modules\Pricing\Application\Services\UpdatePriceListItemService;
use Modules\Pricing\Application\Services\UpdatePriceListService;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListItemRepositoryInterface;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListRepositoryInterface;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PriceListItemModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PriceListModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories\EloquentPriceListItemRepository;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories\EloquentPriceListRepository;

class PricingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PriceListRepositoryInterface::class, fn ($app) =>
            new EloquentPriceListRepository($app->make(PriceListModel::class)));

        $this->app->bind(PriceListItemRepositoryInterface::class, fn ($app) =>
            new EloquentPriceListItemRepository($app->make(PriceListItemModel::class)));

        $this->app->bind(CreatePriceListServiceInterface::class, fn ($app) =>
            new CreatePriceListService($app->make(PriceListRepositoryInterface::class)));

        $this->app->bind(FindPriceListServiceInterface::class, fn ($app) =>
            new FindPriceListService($app->make(PriceListRepositoryInterface::class)));

        $this->app->bind(UpdatePriceListServiceInterface::class, fn ($app) =>
            new UpdatePriceListService($app->make(PriceListRepositoryInterface::class)));

        $this->app->bind(DeletePriceListServiceInterface::class, fn ($app) =>
            new DeletePriceListService($app->make(PriceListRepositoryInterface::class)));

        $this->app->bind(ActivatePriceListServiceInterface::class, fn ($app) =>
            new ActivatePriceListService($app->make(PriceListRepositoryInterface::class)));

        $this->app->bind(DeactivatePriceListServiceInterface::class, fn ($app) =>
            new DeactivatePriceListService($app->make(PriceListRepositoryInterface::class)));

        $this->app->bind(CreatePriceListItemServiceInterface::class, fn ($app) =>
            new CreatePriceListItemService($app->make(PriceListItemRepositoryInterface::class)));

        $this->app->bind(FindPriceListItemServiceInterface::class, fn ($app) =>
            new FindPriceListItemService($app->make(PriceListItemRepositoryInterface::class)));

        $this->app->bind(UpdatePriceListItemServiceInterface::class, fn ($app) =>
            new UpdatePriceListItemService($app->make(PriceListItemRepositoryInterface::class)));

        $this->app->bind(DeletePriceListItemServiceInterface::class, fn ($app) =>
            new DeletePriceListItemService($app->make(PriceListItemRepositoryInterface::class)));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        Route::middleware(['api', 'auth:api', 'resolve.tenant'])
            ->prefix('api')
            ->group(function () {
                $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
            });
    }
}
