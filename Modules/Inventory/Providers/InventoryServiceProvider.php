<?php

declare(strict_types=1);

namespace Modules\Inventory\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Repositories\Contracts\ProductCategoryRepositoryInterface;
use Modules\Inventory\Repositories\Contracts\ProductRepositoryInterface;
use Modules\Inventory\Repositories\Contracts\StockLevelRepositoryInterface;
use Modules\Inventory\Repositories\Contracts\StockLocationRepositoryInterface;
use Modules\Inventory\Repositories\Contracts\StockMovementRepositoryInterface;
use Modules\Inventory\Repositories\Contracts\UnitOfMeasureRepositoryInterface;
use Modules\Inventory\Repositories\Contracts\WarehouseRepositoryInterface;
use Modules\Inventory\Repositories\ProductCategoryRepository;
use Modules\Inventory\Repositories\ProductRepository;
use Modules\Inventory\Repositories\StockLevelRepository;
use Modules\Inventory\Repositories\StockLocationRepository;
use Modules\Inventory\Repositories\StockMovementRepository;
use Modules\Inventory\Repositories\UnitOfMeasureRepository;
use Modules\Inventory\Repositories\WarehouseRepository;

class InventoryServiceProvider extends ServiceProvider
{
    /**
     * @var string
     */
    protected $moduleName = 'Inventory';

    /**
     * @var string
     */
    protected $moduleNameLower = 'inventory';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->app->register(EventServiceProvider::class);

        // Register repositories
        $this->app->bind(
            ProductRepositoryInterface::class,
            ProductRepository::class
        );

        $this->app->bind(
            ProductCategoryRepositoryInterface::class,
            ProductCategoryRepository::class
        );

        $this->app->bind(
            WarehouseRepositoryInterface::class,
            WarehouseRepository::class
        );

        $this->app->bind(
            StockLocationRepositoryInterface::class,
            StockLocationRepository::class
        );

        $this->app->bind(
            StockLevelRepositoryInterface::class,
            StockLevelRepository::class
        );

        $this->app->bind(
            StockMovementRepositoryInterface::class,
            StockMovementRepository::class
        );

        $this->app->bind(
            UnitOfMeasureRepositoryInterface::class,
            UnitOfMeasureRepository::class
        );
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower.'.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'), $this->moduleNameLower
        );
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', $this->moduleNameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'Resources/lang'), $this->moduleNameLower);
            $this->loadJsonTranslationsFrom(module_path($this->moduleName, 'Resources/lang'));
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * Get publishable view paths.
     */
    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (\Config::get('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->moduleNameLower)) {
                $paths[] = $path.'/modules/'.$this->moduleNameLower;
            }
        }

        return $paths;
    }
}
