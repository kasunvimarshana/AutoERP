<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Product\Application\Contracts\ProductCategoryServiceInterface;
use Modules\Product\Application\Contracts\ProductComponentServiceInterface;
use Modules\Product\Application\Contracts\ProductServiceInterface;
use Modules\Product\Application\Contracts\ProductVariantServiceInterface;
use Modules\Product\Application\Services\ProductCategoryService;
use Modules\Product\Application\Services\ProductComponentService;
use Modules\Product\Application\Services\ProductService;
use Modules\Product\Application\Services\ProductVariantService;
use Modules\Product\Domain\RepositoryInterfaces\ProductCategoryRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductComponentRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductVariantRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductCategoryModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductComponentModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductVariantModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductCategoryRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductComponentRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductVariantRepository;

class ProductServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProductCategoryRepositoryInterface::class, fn($app) =>
            new EloquentProductCategoryRepository($app->make(ProductCategoryModel::class))
        );
        $this->app->bind(ProductRepositoryInterface::class, fn($app) =>
            new EloquentProductRepository($app->make(ProductModel::class))
        );
        $this->app->bind(ProductVariantRepositoryInterface::class, fn($app) =>
            new EloquentProductVariantRepository($app->make(ProductVariantModel::class))
        );
        $this->app->bind(ProductComponentRepositoryInterface::class, fn($app) =>
            new EloquentProductComponentRepository($app->make(ProductComponentModel::class))
        );
        $this->app->bind(ProductCategoryServiceInterface::class, fn($app) =>
            new ProductCategoryService($app->make(ProductCategoryRepositoryInterface::class))
        );
        $this->app->bind(ProductServiceInterface::class, fn($app) =>
            new ProductService($app->make(ProductRepositoryInterface::class))
        );
        $this->app->bind(ProductVariantServiceInterface::class, fn($app) =>
            new ProductVariantService(
                $app->make(ProductVariantRepositoryInterface::class),
                $app->make(ProductRepositoryInterface::class),
            )
        );
        $this->app->bind(ProductComponentServiceInterface::class, fn($app) =>
            new ProductComponentService($app->make(ProductComponentRepositoryInterface::class))
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
