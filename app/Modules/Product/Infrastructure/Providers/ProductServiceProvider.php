<?php
declare(strict_types=1);
namespace Modules\Product\Infrastructure\Providers;
use Illuminate\Support\ServiceProvider;
use Modules\Product\Application\Contracts\CategoryServiceInterface;
use Modules\Product\Application\Contracts\ProductServiceInterface;
use Modules\Product\Application\Contracts\ProductVariantServiceInterface;
use Modules\Product\Application\Services\CategoryService;
use Modules\Product\Application\Services\ProductService;
use Modules\Product\Application\Services\ProductVariantService;
use Modules\Product\Domain\RepositoryInterfaces\CategoryRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductVariantRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentCategoryRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductRepository;
use Modules\Product\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductVariantRepository;

class ProductServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CategoryRepositoryInterface::class, EloquentCategoryRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, EloquentProductRepository::class);
        $this->app->bind(ProductVariantRepositoryInterface::class, EloquentProductVariantRepository::class);
        $this->app->bind(CategoryServiceInterface::class, CategoryService::class);
        $this->app->bind(ProductServiceInterface::class, ProductService::class);
        $this->app->bind(ProductVariantServiceInterface::class, ProductVariantService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
