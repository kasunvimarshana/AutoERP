<?php

declare(strict_types=1);

namespace Modules\Product\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Product\Application\Services\ProductAttributeService;
use Modules\Product\Application\Services\ProductImageService;
use Modules\Product\Application\Services\ProductService;
use Modules\Product\Application\Services\UomConversionService;
use Modules\Product\Domain\Contracts\ProductAttributeRepositoryInterface;
use Modules\Product\Domain\Contracts\ProductImageRepositoryInterface;
use Modules\Product\Domain\Contracts\ProductRepositoryInterface;
use Modules\Product\Domain\Contracts\UomConversionRepositoryInterface;
use Modules\Product\Infrastructure\Repositories\ProductAttributeRepository;
use Modules\Product\Infrastructure\Repositories\ProductImageRepository;
use Modules\Product\Infrastructure\Repositories\ProductRepository;
use Modules\Product\Infrastructure\Repositories\UomConversionRepository;

class ProductServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(UomConversionRepositoryInterface::class, UomConversionRepository::class);
        $this->app->bind(ProductImageRepositoryInterface::class, ProductImageRepository::class);
        $this->app->bind(ProductAttributeRepositoryInterface::class, ProductAttributeRepository::class);

        // Application service bindings (resolved via auto-wiring; explicit bindings
        // are registered so that the container resolves them as singletons within
        // the request lifecycle, avoiding repeated instantiation).
        $this->app->singleton(ProductService::class);
        $this->app->singleton(UomConversionService::class);
        $this->app->singleton(ProductImageService::class);
        $this->app->singleton(ProductAttributeService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Infrastructure/Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Interfaces/Http/routes.php');
    }
}
