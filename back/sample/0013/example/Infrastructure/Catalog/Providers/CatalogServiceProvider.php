<?php

declare(strict_types=1);

namespace App\Infrastructure\Catalog\Providers;

use App\Domain\Catalog\Repositories\ProductRepositoryInterface;
use App\Infrastructure\Catalog\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Catalog\Persistence\Repositories\EloquentProductRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * CatalogServiceProvider — Bounded Context Service Provider.
 *
 * Auto-discovered by DddArchitectServiceProvider when auto_discover is enabled.
 * Binds domain repository interfaces to their Eloquent implementations and
 * loads context-scoped routes and migrations.
 */
final class CatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ProductRepositoryInterface::class,
            fn ($app) => new EloquentProductRepository(
                $app->make(ProductModel::class)
            ),
        );
    }

    public function boot(): void
    {
        $this->bootRoutes();
        $this->bootMigrations();
    }

    private function bootRoutes(): void
    {
        $routeFile = __DIR__ . '/../../../../Presentation/Http/Routes/api.php';

        if (file_exists($routeFile)) {
            Route::middleware('api')
                ->prefix('api/v1')
                ->group($routeFile);
        }
    }

    private function bootMigrations(): void
    {
        $migrationsPath = __DIR__ . '/../Migrations';

        if (is_dir($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }
}
