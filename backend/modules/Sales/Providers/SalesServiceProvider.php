<?php

declare(strict_types=1);

namespace Modules\Sales\Providers;

use Illuminate\Support\Facades\Route;
use Modules\Core\Abstracts\BaseModuleServiceProvider;
use Modules\Sales\Repositories\SalesOrderRepository;
use Modules\Sales\Services\SalesOrderService;

class SalesServiceProvider extends BaseModuleServiceProvider
{
    protected string $moduleId = 'sales';

    protected string $moduleName = 'Sales';

    protected string $moduleVersion = '1.0.0';

    protected array $dependencies = ['core', 'inventory'];

    /**
     * Register services.
     */
    public function register(): void
    {
        // Register repositories - Laravel will auto-inject TenantContext
        $this->app->singleton(SalesOrderRepository::class);

        // Register services - Laravel will auto-inject dependencies
        $this->app->singleton(SalesOrderService::class);
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
            __DIR__.'/../config/sales.php' => config_path('sales.php'),
        ], 'sales-config');
    }

    /**
     * Register module routes
     */
    protected function registerRoutes(): void
    {
        Route::group([
            'middleware' => ['api', 'tenant.identify'],
            'prefix' => 'api/sales',
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
                'customers' => [
                    'name' => 'Customers',
                    'singular' => 'Customer',
                    'icon' => 'user-group',
                    'routes' => [
                        'list' => '/sales/customers',
                        'create' => '/sales/customers/create',
                        'edit' => '/sales/customers/{id}/edit',
                        'view' => '/sales/customers/{id}',
                    ],
                ],
                'quotations' => [
                    'name' => 'Quotations',
                    'singular' => 'Quotation',
                    'icon' => 'document-text',
                    'routes' => [
                        'list' => '/sales/quotations',
                        'create' => '/sales/quotations/create',
                        'edit' => '/sales/quotations/{id}/edit',
                        'view' => '/sales/quotations/{id}',
                    ],
                ],
                'sales-orders' => [
                    'name' => 'Sales Orders',
                    'singular' => 'Sales Order',
                    'icon' => 'shopping-cart',
                    'routes' => [
                        'list' => '/sales/sales-orders',
                        'create' => '/sales/sales-orders/create',
                        'edit' => '/sales/sales-orders/{id}/edit',
                        'view' => '/sales/sales-orders/{id}',
                    ],
                ],
            ],
            'features' => [
                'quotations' => true,
                'recurring_orders' => true,
                'customer_portal' => true,
                'price_lists' => true,
                'credit_limits' => true,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions(): array
    {
        return [
            'sales.customers.view',
            'sales.customers.create',
            'sales.customers.update',
            'sales.customers.delete',
            'sales.quotations.view',
            'sales.quotations.create',
            'sales.quotations.update',
            'sales.quotations.delete',
            'sales.quotations.approve',
            'sales.sales-orders.view',
            'sales.sales-orders.create',
            'sales.sales-orders.update',
            'sales.sales-orders.delete',
            'sales.sales-orders.approve',
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
                'path' => '/api/sales/customers',
                'name' => 'sales.customers.index',
                'permission' => 'sales.customers.view',
            ],
            [
                'method' => 'POST',
                'path' => '/api/sales/customers',
                'name' => 'sales.customers.store',
                'permission' => 'sales.customers.create',
            ],
            [
                'method' => 'GET',
                'path' => '/api/sales/sales-orders',
                'name' => 'sales.sales-orders.index',
                'permission' => 'sales.sales-orders.view',
            ],
            [
                'method' => 'POST',
                'path' => '/api/sales/sales-orders',
                'name' => 'sales.sales-orders.store',
                'permission' => 'sales.sales-orders.create',
            ],
        ];
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            SalesOrderRepository::class,
            SalesOrderService::class,
        ];
    }
}
