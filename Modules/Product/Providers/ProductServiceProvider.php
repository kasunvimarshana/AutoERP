<?php

declare(strict_types=1);

namespace Modules\Product\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductCategory;
use Modules\Product\Models\Unit;
use Modules\Product\Policies\ProductCategoryPolicy;
use Modules\Product\Policies\ProductPolicy;
use Modules\Product\Policies\UnitPolicy;

/**
 * ProductServiceProvider
 *
 * Bootstraps the product module
 */
class ProductServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register module configuration
        $this->mergeConfigFrom(
            __DIR__.'/../Config/product.php',
            'product'
        );

        // Register repositories
        $this->app->singleton(\Modules\Product\Repositories\ProductRepository::class);
        $this->app->singleton(\Modules\Product\Repositories\ProductCategoryRepository::class);
        $this->app->singleton(\Modules\Product\Repositories\UnitRepository::class);
        $this->app->singleton(\Modules\Product\Repositories\ProductUnitConversionRepository::class);

        // Register services
        $this->app->singleton(\Modules\Product\Services\ProductService::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../Config/product.php' => config_path('product.php'),
        ], 'product-config');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        // Register policies
        $this->registerPolicies();
    }

    /**
     * Register policies for the module
     */
    protected function registerPolicies(): void
    {
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(ProductCategory::class, ProductCategoryPolicy::class);
        Gate::policy(Unit::class, UnitPolicy::class);
    }
}
