<?php

declare(strict_types=1);

namespace Modules\POS\Providers;

use Illuminate\Support\Facades\Route;
use Modules\Core\Abstracts\BaseModuleServiceProvider;
use Modules\POS\Repositories\TransactionRepository;
use Modules\POS\Repositories\BusinessLocationRepository;
use Modules\POS\Repositories\CashRegisterRepository;
use Modules\POS\Services\TransactionService;
use Modules\POS\Services\CashRegisterService;
use Modules\POS\Services\StockAdjustmentService;
use Modules\POS\Services\BusinessLocationService;
use Modules\POS\Services\ReferenceNumberService;

class POSServiceProvider extends BaseModuleServiceProvider
{
    protected string $moduleId = 'pos';

    protected string $moduleName = 'Point of Sale';

    protected string $moduleVersion = '1.0.0';

    protected array $dependencies = ['core', 'inventory'];

    public function register(): void
    {
        // Register repositories
        $this->app->singleton(TransactionRepository::class);
        $this->app->singleton(BusinessLocationRepository::class);
        $this->app->singleton(CashRegisterRepository::class);

        // Register services
        $this->app->singleton(ReferenceNumberService::class);
        $this->app->singleton(TransactionService::class);
        $this->app->singleton(CashRegisterService::class);
        $this->app->singleton(StockAdjustmentService::class);
        $this->app->singleton(BusinessLocationService::class);
    }

    public function boot(): void
    {
        $this->registerRoutes();
        $this->loadModuleMigrations();
        $this->loadModuleViews();

        // Publish config
        $this->publishes([
            __DIR__.'/../config/pos.php' => config_path('pos.php'),
        ], 'pos-config');
    }

    protected function registerRoutes(): void
    {
        Route::group([
            'middleware' => ['api', 'tenant.identify'],
            'prefix' => 'api/pos',
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        });
    }

    public function getModuleConfig(): array
    {
        return [
            'entities' => [
                'transactions' => [
                    'name' => 'Transactions',
                    'icon' => 'receipt',
                    'routes' => [
                        'list' => '/pos/transactions',
                        'create' => '/pos/transactions/create',
                        'edit' => '/pos/transactions/{id}/edit',
                        'view' => '/pos/transactions/{id}',
                    ],
                ],
                'locations' => [
                    'name' => 'Business Locations',
                    'icon' => 'store',
                    'routes' => [
                        'list' => '/pos/locations',
                        'create' => '/pos/locations/create',
                    ],
                ],
                'cash-registers' => [
                    'name' => 'Cash Registers',
                    'icon' => 'cash-register',
                    'routes' => [
                        'list' => '/pos/cash-registers',
                    ],
                ],
            ],
            'features' => [
                'multi_location' => true,
                'cash_register' => true,
                'product_variations' => true,
                'customer_groups' => true,
                'tax_groups' => true,
                'stock_adjustments' => true,
                'restaurant_mode' => true,
                'barcode_labels' => true,
                'invoice_customization' => true,
            ],
        ];
    }

    public function getPermissions(): array
    {
        return [
            'pos.transactions.view',
            'pos.transactions.create',
            'pos.transactions.edit',
            'pos.transactions.delete',
            'pos.locations.view',
            'pos.locations.create',
            'pos.locations.edit',
            'pos.locations.delete',
            'pos.cash-registers.view',
            'pos.cash-registers.open',
            'pos.cash-registers.close',
            'pos.stock-adjustments.view',
            'pos.stock-adjustments.create',
            'pos.expenses.view',
            'pos.expenses.create',
            'pos.settings.manage',
        ];
    }

    public function getRoutes(): array
    {
        return [
            'api' => [
                'prefix' => 'api/pos',
                'middleware' => ['api', 'tenant.identify'],
            ],
            'web' => [
                'prefix' => 'pos',
                'middleware' => ['web', 'auth'],
            ],
        ];
    }

    public function provides(): array
    {
        return [
            TransactionRepository::class,
            BusinessLocationRepository::class,
            CashRegisterRepository::class,
            TransactionService::class,
            CashRegisterService::class,
            StockAdjustmentService::class,
            BusinessLocationService::class,
            ReferenceNumberService::class,
        ];
    }
}
