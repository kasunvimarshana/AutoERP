<?php

namespace App\Modules\Inventory\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class InventoryServiceProvider extends ServiceProvider
{
    protected string $moduleName      = 'Inventory';
    protected string $moduleNameLower = 'inventory';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/inventory.php', $this->moduleNameLower);

        // Bind interfaces to implementations
        $this->app->bind(
            \App\Modules\Inventory\Contracts\InventoryRepositoryInterface::class,
            \App\Modules\Inventory\Repositories\InventoryRepository::class
        );
        $this->app->bind(
            \App\Modules\Inventory\Contracts\StockLevelRepositoryInterface::class,
            \App\Modules\Inventory\Repositories\StockLevelRepository::class
        );

        // Singleton services
        $this->app->singleton(\App\Modules\Valuation\Services\InventoryValuationService::class);
    }

    public function boot(): void
    {
        $this->registerMigrations();
        $this->registerRoutes();
        $this->registerViews();
        $this->registerTranslations();
        $this->registerObservers();
        $this->registerEventListeners();
    }

    protected function registerMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function registerRoutes(): void
    {
        if (! $this->app->routesAreCached()) {
            Route::prefix('api/v1')
                ->middleware(['api', 'auth:sanctum', 'tenant'])
                ->namespace('App\\Modules\\Inventory\\Http\\Controllers')
                ->group(__DIR__ . '/../routes/api.php');
        }
    }

    protected function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', $this->moduleNameLower);
    }

    protected function registerTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', $this->moduleNameLower);
    }

    protected function registerObservers(): void
    {
        \App\Modules\Inventory\Models\StockLevel::observe(\App\Modules\Inventory\Observers\StockLevelObserver::class);
        \App\Modules\Inventory\Models\TrackingLot::observe(\App\Modules\Inventory\Observers\TrackingLotObserver::class);
        \App\Modules\Inventory\Models\SerialNumber::observe(\App\Modules\Inventory\Observers\SerialNumberObserver::class);
    }

    protected function registerEventListeners(): void
    {
        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\StockMovement\Events\StockMovementCompleted::class,
            \App\Modules\Inventory\Listeners\UpdateStockOnMovementCompleted::class
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Returns\Events\ReturnProcessed::class,
            \App\Modules\Inventory\Listeners\AdjustStockOnReturn::class
        );
    }

    public function provides(): array
    {
        return [
            \App\Modules\Inventory\Contracts\InventoryRepositoryInterface::class,
            \App\Modules\Inventory\Contracts\StockLevelRepositoryInterface::class,
        ];
    }
}
