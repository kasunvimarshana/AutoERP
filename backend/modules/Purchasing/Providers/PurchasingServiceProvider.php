<?php

declare(strict_types=1);

namespace Modules\Purchasing\Providers;

use Illuminate\Support\Facades\Route;
use Modules\Core\Abstracts\BaseModuleServiceProvider;
use Modules\Purchasing\Repositories\PurchaseOrderRepository;
use Modules\Purchasing\Repositories\SupplierRepository;
use Modules\Purchasing\Services\PurchaseOrderService;
use Modules\Purchasing\Services\SupplierService;

class PurchasingServiceProvider extends BaseModuleServiceProvider
{
    protected string $moduleId = 'purchasing';

    protected string $moduleName = 'Purchasing';

    protected string $moduleVersion = '1.0.0';

    protected array $dependencies = ['core', 'inventory'];

    /**
     * Register services.
     */
    public function register(): void
    {
        // Register repositories - Laravel will auto-inject TenantContext
        $this->app->singleton(SupplierRepository::class);
        $this->app->singleton(PurchaseOrderRepository::class);

        // Register services - Laravel will auto-inject dependencies
        $this->app->singleton(SupplierService::class);
        $this->app->singleton(PurchaseOrderService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register routes
        $this->registerRoutes();

        // Load migrations
        $this->loadModuleMigrations();

        // Load views
        $this->loadModuleViews();

        // Publish config
        $this->publishes([
            __DIR__.'/../config/purchasing.php' => config_path('purchasing.php'),
        ], 'purchasing-config');
    }

    /**
     * Register module routes
     */
    protected function registerRoutes(): void
    {
        Route::group([
            'middleware' => ['api', 'tenant.identify'],
            'prefix' => 'api/purchasing',
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
                'suppliers' => [
                    'name' => 'Suppliers',
                    'singular' => 'Supplier',
                    'icon' => 'truck',
                    'routes' => [
                        'list' => '/purchasing/suppliers',
                        'create' => '/purchasing/suppliers/create',
                        'edit' => '/purchasing/suppliers/{id}/edit',
                        'view' => '/purchasing/suppliers/{id}',
                    ],
                ],
                'purchase-orders' => [
                    'name' => 'Purchase Orders',
                    'singular' => 'Purchase Order',
                    'icon' => 'shopping-bag',
                    'routes' => [
                        'list' => '/purchasing/purchase-orders',
                        'create' => '/purchasing/purchase-orders/create',
                        'edit' => '/purchasing/purchase-orders/{id}/edit',
                        'view' => '/purchasing/purchase-orders/{id}',
                    ],
                ],
                'goods-receipts' => [
                    'name' => 'Goods Receipts',
                    'singular' => 'Goods Receipt',
                    'icon' => 'clipboard-check',
                    'routes' => [
                        'list' => '/purchasing/goods-receipts',
                        'create' => '/purchasing/goods-receipts/create',
                        'edit' => '/purchasing/goods-receipts/{id}/edit',
                        'view' => '/purchasing/goods-receipts/{id}',
                    ],
                ],
            ],
            'features' => [
                'purchase_requisitions' => true,
                'three_way_matching' => true,
                'supplier_portal' => true,
                'rfq_management' => true,
                'contract_management' => true,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions(): array
    {
        return [
            'purchasing.suppliers.view',
            'purchasing.suppliers.create',
            'purchasing.suppliers.update',
            'purchasing.suppliers.delete',
            'purchasing.purchase-orders.view',
            'purchasing.purchase-orders.create',
            'purchasing.purchase-orders.update',
            'purchasing.purchase-orders.delete',
            'purchasing.purchase-orders.approve',
            'purchasing.goods-receipts.view',
            'purchasing.goods-receipts.create',
            'purchasing.goods-receipts.update',
            'purchasing.goods-receipts.delete',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes(): array
    {
        return [
            [
                'method' => 'GET',
                'path' => '/api/purchasing/suppliers',
                'name' => 'purchasing.suppliers.index',
                'permission' => 'purchasing.suppliers.view',
            ],
            [
                'method' => 'POST',
                'path' => '/api/purchasing/suppliers',
                'name' => 'purchasing.suppliers.store',
                'permission' => 'purchasing.suppliers.create',
            ],
            [
                'method' => 'GET',
                'path' => '/api/purchasing/purchase-orders',
                'name' => 'purchasing.purchase-orders.index',
                'permission' => 'purchasing.purchase-orders.view',
            ],
            [
                'method' => 'POST',
                'path' => '/api/purchasing/purchase-orders',
                'name' => 'purchasing.purchase-orders.store',
                'permission' => 'purchasing.purchase-orders.create',
            ],
        ];
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            SupplierRepository::class,
            PurchaseOrderRepository::class,
            SupplierService::class,
            PurchaseOrderService::class,
        ];
    }
}
