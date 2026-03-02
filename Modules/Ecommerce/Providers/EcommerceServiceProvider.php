<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Ecommerce\Application\Services\CartService;
use Modules\Ecommerce\Application\Services\StorefrontOrderService;
use Modules\Ecommerce\Application\Services\StorefrontProductService;
use Modules\Ecommerce\Domain\Contracts\StorefrontCartRepositoryInterface;
use Modules\Ecommerce\Domain\Contracts\StorefrontOrderRepositoryInterface;
use Modules\Ecommerce\Domain\Contracts\StorefrontProductRepositoryInterface;
use Modules\Ecommerce\Infrastructure\Repositories\StorefrontCartRepository;
use Modules\Ecommerce\Infrastructure\Repositories\StorefrontOrderRepository;
use Modules\Ecommerce\Infrastructure\Repositories\StorefrontProductRepository;

class EcommerceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StorefrontProductRepositoryInterface::class, StorefrontProductRepository::class);
        $this->app->bind(StorefrontCartRepositoryInterface::class, StorefrontCartRepository::class);
        $this->app->bind(StorefrontOrderRepositoryInterface::class, StorefrontOrderRepository::class);

        $this->app->singleton(StorefrontProductService::class);
        $this->app->singleton(CartService::class);
        $this->app->singleton(StorefrontOrderService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Infrastructure/Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Interfaces/Http/routes.php');
    }
}
