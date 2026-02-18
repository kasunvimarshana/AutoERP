<?php

declare(strict_types=1);

namespace Modules\Inventory\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Modules\Core\Abstracts\BaseModuleServiceProvider;
use Modules\Inventory\Events\LowStockAlert;
use Modules\Inventory\Events\ProductCreated;
use Modules\Inventory\Events\StockAdjusted;
use Modules\Inventory\Listeners\SendLowStockNotification;
use Modules\Inventory\Listeners\SendProductCreatedNotification;
use Modules\Inventory\Listeners\SendStockAdjustmentNotification;
use Modules\Inventory\Repositories\ProductRepository;
use Modules\Inventory\Repositories\StockLedgerRepository;
use Modules\Inventory\Repositories\WarehouseRepository;
use Modules\Inventory\Services\ProductService;
use Modules\Inventory\Services\StockService;

class InventoryServiceProvider extends BaseModuleServiceProvider
{
    protected string $moduleId = 'inventory';

    protected string $moduleName = 'Inventory Management';

    protected string $moduleVersion = '1.0.0';

    protected array $dependencies = ['core'];

    /**
     * Register services.
     */
    public function register(): void
    {
        // Register repositories
        $this->app->singleton(ProductRepository::class);
        $this->app->singleton(StockLedgerRepository::class);
        $this->app->singleton(WarehouseRepository::class);

        // Register services
        $this->app->singleton(ProductService::class);
        $this->app->singleton(StockService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerRoutes();
        $this->loadModuleMigrations();
        $this->loadModuleViews();
        $this->registerEventListeners();

        // Publish config
        $this->publishes([
            __DIR__.'/../config/inventory.php' => config_path('inventory.php'),
        ], 'inventory-config');
    }

    /**
     * Register event listeners for inventory events
     */
    protected function registerEventListeners(): void
    {
        Event::listen(LowStockAlert::class, SendLowStockNotification::class);
        Event::listen(ProductCreated::class, SendProductCreatedNotification::class);
        Event::listen(StockAdjusted::class, SendStockAdjustmentNotification::class);
    }

    /**
     * Register module routes
     */
    protected function registerRoutes(): void
    {
        Route::group([
            'middleware' => ['api', 'tenant.identify'],
            'prefix' => 'api/inventory',
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getModuleConfig(): array
    {
        return [
            'entities' => [
                'products' => [
                    'name' => 'Products',
                    'icon' => 'cube',
                    'routes' => [
                        'list' => '/inventory/products',
                        'create' => '/inventory/products/create',
                        'edit' => '/inventory/products/{id}/edit',
                        'view' => '/inventory/products/{id}',
                    ],
                ],
                'stock' => [
                    'name' => 'Stock Management',
                    'icon' => 'warehouse',
                    'routes' => [
                        'list' => '/inventory/stock',
                        'adjust' => '/inventory/stock/adjust',
                    ],
                ],
                'warehouses' => [
                    'name' => 'Warehouses',
                    'icon' => 'building',
                    'routes' => [
                        'list' => '/inventory/warehouses',
                        'create' => '/inventory/warehouses/create',
                    ],
                ],
            ],
            'features' => [
                'multi_warehouse' => true,
                'batch_tracking' => true,
                'serial_tracking' => true,
                'lot_tracking' => true,
                'expiry_tracking' => true,
                'multi_uom' => true,
                'valuation_methods' => ['FIFO', 'LIFO', 'AVERAGE', 'STANDARD'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions(): array
    {
        return [
            'inventory.products.view',
            'inventory.products.create',
            'inventory.products.edit',
            'inventory.products.delete',
            'inventory.stock.view',
            'inventory.stock.adjust',
            'inventory.stock.transfer',
            'inventory.warehouses.view',
            'inventory.warehouses.create',
            'inventory.warehouses.edit',
            'inventory.warehouses.delete',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes(): array
    {
        return [
            'api' => [
                'prefix' => 'api/inventory',
                'middleware' => ['api', 'tenant.identify'],
            ],
            'web' => [
                'prefix' => 'inventory',
                'middleware' => ['web', 'auth'],
            ],
        ];
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            ProductRepository::class,
            StockLedgerRepository::class,
            WarehouseRepository::class,
            ProductService::class,
            StockService::class,
        ];
    }
}
