<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Product\Application\Contracts\CategoryServiceInterface;
use Modules\Product\Application\Contracts\ProductServiceInterface;
use Modules\Product\Application\Contracts\ProductVariantServiceInterface;
use Modules\Product\Application\Contracts\UnitOfMeasureServiceInterface;
use Modules\Product\Application\Services\CategoryService;
use Modules\Product\Application\Services\ProductService;
use Modules\Product\Application\Services\ProductVariantService;
use Modules\Product\Application\Services\UnitOfMeasureService;
use Modules\Product\Domain\RepositoryInterfaces\CategoryRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductVariantRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\UnitOfMeasureRepositoryInterface;
use Modules\Product\Infrastructure\Http\Controllers\CategoryController;
use Modules\Product\Infrastructure\Http\Controllers\ProductController;
use Modules\Product\Infrastructure\Http\Controllers\ProductVariantController;
use Modules\Product\Infrastructure\Http\Controllers\UnitOfMeasureController;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\CategoryModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductVariantModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\UnitOfMeasureModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentCategoryRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductVariantRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentUnitOfMeasureRepository;

final class ProductServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            CategoryRepositoryInterface::class,
            static fn ($app) => new EloquentCategoryRepository($app->make(CategoryModel::class))
        );

        $this->app->bind(
            ProductRepositoryInterface::class,
            static fn ($app) => new EloquentProductRepository($app->make(ProductModel::class))
        );

        $this->app->bind(
            ProductVariantRepositoryInterface::class,
            static fn ($app) => new EloquentProductVariantRepository($app->make(ProductVariantModel::class))
        );

        $this->app->bind(
            UnitOfMeasureRepositoryInterface::class,
            static fn ($app) => new EloquentUnitOfMeasureRepository($app->make(UnitOfMeasureModel::class))
        );

        $this->app->singleton(
            CategoryServiceInterface::class,
            static fn ($app) => new CategoryService(
                $app->make(CategoryRepositoryInterface::class)
            )
        );

        $this->app->singleton(
            ProductServiceInterface::class,
            static fn ($app) => new ProductService(
                $app->make(ProductRepositoryInterface::class)
            )
        );

        $this->app->singleton(
            ProductVariantServiceInterface::class,
            static fn ($app) => new ProductVariantService(
                $app->make(ProductVariantRepositoryInterface::class)
            )
        );

        $this->app->singleton(
            UnitOfMeasureServiceInterface::class,
            static fn ($app) => new UnitOfMeasureService(
                $app->make(UnitOfMeasureRepositoryInterface::class)
            )
        );

        $this->mergeConfigFrom(__DIR__ . '/../../config/product.php', 'product');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->registerRoutes();

        $this->publishes([
            __DIR__ . '/../../config/product.php' => config_path('product.php'),
        ], 'product-config');

        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'product-migrations');
    }

    private function registerRoutes(): void
    {
        Route::middleware(['api', 'auth:api'])
            ->prefix('api/product')
            ->group(static function (): void {
                Route::apiResource('categories', CategoryController::class);
                Route::apiResource('products', ProductController::class);
                Route::apiResource('product-variants', ProductVariantController::class);
                Route::apiResource('unit-of-measures', UnitOfMeasureController::class);
            });
    }
}
