<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Product\Application\Contracts\Gs1BarcodeServiceInterface;
use Modules\Product\Application\Contracts\ProductCategoryServiceInterface;
use Modules\Product\Application\Contracts\ProductServiceInterface;
use Modules\Product\Application\Contracts\ProductVariantServiceInterface;
use Modules\Product\Application\Contracts\UomConversionServiceInterface;
use Modules\Product\Application\Services\Gs1BarcodeService;
use Modules\Product\Application\Services\ProductCategoryService;
use Modules\Product\Application\Services\ProductService;
use Modules\Product\Application\Services\ProductVariantService;
use Modules\Product\Application\Services\UomConversionService;
use Modules\Product\Domain\Contracts\Repositories\ProductCategoryRepositoryInterface;
use Modules\Product\Domain\Contracts\Repositories\ProductRepositoryInterface;
use Modules\Product\Domain\Contracts\Repositories\ProductVariantRepositoryInterface;
use Modules\Product\Domain\Contracts\Repositories\UnitOfMeasureRepositoryInterface;
use Modules\Product\Domain\Contracts\Repositories\UomConversionRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductCategoryModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductVariantModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\UnitOfMeasureModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\UomConversionModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductCategoryRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductVariantRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentUnitOfMeasureRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentUomConversionRepository;

class ProductServiceProvider extends ServiceProvider
{
    /**
     * Register Product module bindings.
     */
    public function register(): void
    {
        // Repositories
        $this->app->bind(ProductCategoryRepositoryInterface::class, function ($app) {
            return new EloquentProductCategoryRepository($app->make(ProductCategoryModel::class));
        });

        $this->app->bind(ProductRepositoryInterface::class, function ($app) {
            return new EloquentProductRepository($app->make(ProductModel::class));
        });

        $this->app->bind(ProductVariantRepositoryInterface::class, function ($app) {
            return new EloquentProductVariantRepository($app->make(ProductVariantModel::class));
        });

        $this->app->bind(UnitOfMeasureRepositoryInterface::class, function ($app) {
            return new EloquentUnitOfMeasureRepository($app->make(UnitOfMeasureModel::class));
        });

        $this->app->bind(UomConversionRepositoryInterface::class, function ($app) {
            return new EloquentUomConversionRepository($app->make(UomConversionModel::class));
        });

        // Services
        $this->app->bind(ProductCategoryServiceInterface::class, function ($app) {
            return new ProductCategoryService($app->make(ProductCategoryRepositoryInterface::class));
        });

        $this->app->bind(ProductServiceInterface::class, function ($app) {
            return new ProductService($app->make(ProductRepositoryInterface::class));
        });

        $this->app->bind(ProductVariantServiceInterface::class, function ($app) {
            return new ProductVariantService($app->make(ProductVariantRepositoryInterface::class));
        });

        $this->app->bind(UomConversionServiceInterface::class, function ($app) {
            return new UomConversionService($app->make(UomConversionRepositoryInterface::class));
        });

        $this->app->bind(Gs1BarcodeServiceInterface::class, function ($app) {
            return new Gs1BarcodeService($app->make(ProductRepositoryInterface::class));
        });
    }

    /**
     * Boot the Product service provider.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
