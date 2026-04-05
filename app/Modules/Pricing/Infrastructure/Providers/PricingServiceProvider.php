<?php
declare(strict_types=1);
namespace Modules\Pricing\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Pricing\Application\Contracts\ResolvePriceServiceInterface;
use Modules\Pricing\Application\Services\ResolvePriceService;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListItemRepositoryInterface;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListRepositoryInterface;
use Modules\Pricing\Domain\RepositoryInterfaces\TaxRateRepositoryInterface;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PriceListItemModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PriceListModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\TaxRateModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories\EloquentPriceListItemRepository;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories\EloquentPriceListRepository;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories\EloquentTaxRateRepository;

class PricingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PriceListRepositoryInterface::class, fn($app) =>
            new EloquentPriceListRepository($app->make(PriceListModel::class))
        );
        $this->app->bind(TaxRateRepositoryInterface::class, fn($app) =>
            new EloquentTaxRateRepository($app->make(TaxRateModel::class))
        );
        $this->app->bind(PriceListItemRepositoryInterface::class, fn($app) =>
            new EloquentPriceListItemRepository($app->make(PriceListItemModel::class))
        );
        $this->app->bind(ResolvePriceServiceInterface::class, fn($app) =>
            new ResolvePriceService($app->make(PriceListItemRepositoryInterface::class))
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
