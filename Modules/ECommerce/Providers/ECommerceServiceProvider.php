<?php

namespace Modules\ECommerce\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\ECommerce\Domain\Contracts\ECommerceOrderLineRepositoryInterface;
use Modules\ECommerce\Domain\Contracts\ECommerceOrderRepositoryInterface;
use Modules\ECommerce\Domain\Contracts\ProductListingRepositoryInterface;
use Modules\ECommerce\Infrastructure\Repositories\ECommerceOrderLineRepository;
use Modules\ECommerce\Infrastructure\Repositories\ECommerceOrderRepository;
use Modules\ECommerce\Infrastructure\Repositories\ProductListingRepository;

class ECommerceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProductListingRepositoryInterface::class, ProductListingRepository::class);
        $this->app->bind(ECommerceOrderRepositoryInterface::class, ECommerceOrderRepository::class);
        $this->app->bind(ECommerceOrderLineRepositoryInterface::class, ECommerceOrderLineRepository::class);
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'ecommerce');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
    }
}
