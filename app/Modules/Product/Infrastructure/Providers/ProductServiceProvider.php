<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Product\Application\Contracts\CreateProductServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductImageServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductServiceInterface;
use Modules\Product\Application\Contracts\UploadProductImageServiceInterface;
use Modules\Product\Application\Services\CreateProductService;
use Modules\Product\Application\Services\DeleteProductImageService;
use Modules\Product\Application\Services\DeleteProductService;
use Modules\Product\Application\Services\UpdateProductService;
use Modules\Product\Application\Services\UploadProductImageService;
use Modules\Product\Domain\RepositoryInterfaces\ProductImageRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductImageModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductImageRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductRepository;

class ProductServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProductRepositoryInterface::class, function ($app) {
            return new EloquentProductRepository($app->make(ProductModel::class));
        });

        $this->app->bind(ProductImageRepositoryInterface::class, function ($app) {
            return new EloquentProductImageRepository($app->make(ProductImageModel::class));
        });

        $this->app->bind(CreateProductServiceInterface::class, function ($app) {
            return new CreateProductService($app->make(ProductRepositoryInterface::class));
        });

        $this->app->bind(UpdateProductServiceInterface::class, function ($app) {
            return new UpdateProductService($app->make(ProductRepositoryInterface::class));
        });

        $this->app->bind(DeleteProductServiceInterface::class, function ($app) {
            return new DeleteProductService($app->make(ProductRepositoryInterface::class));
        });

        $this->app->bind(UploadProductImageServiceInterface::class, function ($app) {
            return new UploadProductImageService(
                $app->make(ProductRepositoryInterface::class),
                $app->make(ProductImageRepositoryInterface::class),
                $app->make(FileStorageServiceInterface::class)
            );
        });

        $this->app->bind(DeleteProductImageServiceInterface::class, function ($app) {
            return new DeleteProductImageService(
                $app->make(ProductImageRepositoryInterface::class),
                $app->make(FileStorageServiceInterface::class)
            );
        });
    }

    public function boot(): void
    {
        Route::middleware('api')
             ->prefix('api')
             ->group(function () {
                 $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
             });

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
