<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Product\Application\Services\UomService;
use Modules\Product\Domain\Contracts\ProductRepositoryContract;
use Modules\Product\Domain\Contracts\UomRepositoryContract;
use Modules\Product\Infrastructure\Repositories\ProductRepository;
use Modules\Product\Infrastructure\Repositories\UomRepository;

/**
 * Product module service provider.
 *
 * Registers repository bindings, loads migrations and routes.
 */
class ProductServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ProductRepositoryContract::class,
            ProductRepository::class,
        );

        $this->app->bind(
            UomRepositoryContract::class,
            UomRepository::class,
        );

        $this->app->bind(UomService::class, function ($app) {
            return new UomService($app->make(UomRepositoryContract::class));
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(
            __DIR__.'/../Database/Migrations'
        );

        $this->loadRoutesFrom(
            __DIR__.'/../../routes/api.php'
        );
    }
}
