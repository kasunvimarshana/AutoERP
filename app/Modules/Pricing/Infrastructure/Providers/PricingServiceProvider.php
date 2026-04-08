<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Pricing\Application\Contracts\PriceListServiceInterface;
use Modules\Pricing\Application\Contracts\PriceRuleServiceInterface;
use Modules\Pricing\Application\Services\PriceListService;
use Modules\Pricing\Application\Services\PriceRuleService;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListRepositoryInterface;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceRuleRepositoryInterface;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories\EloquentPriceListRepository;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories\EloquentPriceRuleRepository;

class PricingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PriceListRepositoryInterface::class, EloquentPriceListRepository::class);
        $this->app->bind(PriceRuleRepositoryInterface::class, EloquentPriceRuleRepository::class);
        $this->app->bind(PriceListServiceInterface::class, PriceListService::class);
        $this->app->bind(PriceRuleServiceInterface::class, PriceRuleService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
