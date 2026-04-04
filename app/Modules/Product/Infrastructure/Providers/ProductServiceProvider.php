<?php
namespace Modules\Product\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Product\Application\Contracts\CreateProductCategoryServiceInterface;
use Modules\Product\Application\Contracts\CreateProductServiceInterface;
use Modules\Product\Application\Contracts\CreateProductVariantServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductCategoryServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductVariantServiceInterface;
use Modules\Product\Application\Contracts\ProductCategoryTreeServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductCategoryServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductVariantServiceInterface;
use Modules\Product\Application\Services\CreateProductCategoryService;
use Modules\Product\Application\Services\CreateProductService;
use Modules\Product\Application\Services\CreateProductVariantService;
use Modules\Product\Application\Services\DeleteProductCategoryService;
use Modules\Product\Application\Services\DeleteProductService;
use Modules\Product\Application\Services\DeleteProductVariantService;
use Modules\Product\Application\Services\ProductCategoryTreeService;
use Modules\Product\Application\Services\UpdateProductCategoryService;
use Modules\Product\Application\Services\UpdateProductService;
use Modules\Product\Application\Services\UpdateProductVariantService;
use Modules\Product\Domain\RepositoryInterfaces\ProductCategoryRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductVariantRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductCategoryRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductVariantRepository;

class ProductServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProductCategoryRepositoryInterface::class, EloquentProductCategoryRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, EloquentProductRepository::class);
        $this->app->bind(ProductVariantRepositoryInterface::class, EloquentProductVariantRepository::class);

        $this->app->bind(CreateProductCategoryServiceInterface::class, CreateProductCategoryService::class);
        $this->app->bind(UpdateProductCategoryServiceInterface::class, UpdateProductCategoryService::class);
        $this->app->bind(DeleteProductCategoryServiceInterface::class, DeleteProductCategoryService::class);
        $this->app->bind(CreateProductServiceInterface::class, CreateProductService::class);
        $this->app->bind(UpdateProductServiceInterface::class, UpdateProductService::class);
        $this->app->bind(DeleteProductServiceInterface::class, DeleteProductService::class);
        $this->app->bind(CreateProductVariantServiceInterface::class, CreateProductVariantService::class);
        $this->app->bind(UpdateProductVariantServiceInterface::class, UpdateProductVariantService::class);
        $this->app->bind(DeleteProductVariantServiceInterface::class, DeleteProductVariantService::class);
        $this->app->bind(ProductCategoryTreeServiceInterface::class, ProductCategoryTreeService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
