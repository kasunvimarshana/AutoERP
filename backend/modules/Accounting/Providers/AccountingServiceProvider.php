<?php

declare(strict_types=1);

namespace Modules\Accounting\Providers;

use Illuminate\Support\Facades\Route;
use Modules\Core\Abstracts\BaseModuleServiceProvider;

class AccountingServiceProvider extends BaseModuleServiceProvider
{
    protected string $moduleId = 'accounting';

    protected string $moduleName = 'Accounting';

    protected string $moduleVersion = '1.0.0';

    protected array $dependencies = ['core'];

    public function register(): void
    {
        $this->registerRepositories();
        $this->registerServices();
    }

    public function boot(): void
    {
        $this->bootRoutes();
        $this->loadModuleMigrations();
        $this->loadModuleViews();
        $this->bootConfig();
    }

    protected function registerRepositories(): void
    {
        $repositories = [
            \Modules\Accounting\Repositories\AccountRepository::class,
            \Modules\Accounting\Repositories\JournalEntryRepository::class,
            \Modules\Accounting\Repositories\InvoiceRepository::class,
            \Modules\Accounting\Repositories\PaymentRepository::class,
        ];

        foreach ($repositories as $repositoryClass) {
            $this->app->singleton($repositoryClass);
        }
    }

    protected function registerServices(): void
    {
        $services = [
            \Modules\Accounting\Services\AccountService::class,
            \Modules\Accounting\Services\JournalEntryService::class,
            \Modules\Accounting\Services\InvoiceService::class,
            \Modules\Accounting\Services\PaymentService::class,
        ];

        foreach ($services as $serviceClass) {
            $this->app->singleton($serviceClass);
        }
    }

    protected function bootRoutes(): void
    {
        Route::group([
            'middleware' => ['api', 'tenant.identify'],
            'prefix' => 'api/accounting',
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        });
    }

    protected function bootConfig(): void
    {
        $configPath = __DIR__.'/../config/accounting.php';

        $this->publishes([
            $configPath => config_path('accounting.php'),
        ], 'accounting-config');
    }

    /**
     * {@inheritdoc}
     */
    public function getModuleConfig(): array
    {
        return [
            'entities' => [
                'accounts' => [
                    'name' => 'Chart of Accounts',
                    'singular' => 'Account',
                    'icon' => 'book-open',
                    'routes' => [
                        'list' => '/accounting/accounts',
                        'create' => '/accounting/accounts/create',
                        'edit' => '/accounting/accounts/{id}/edit',
                        'view' => '/accounting/accounts/{id}',
                    ],
                ],
                'journal-entries' => [
                    'name' => 'Journal Entries',
                    'singular' => 'Journal Entry',
                    'icon' => 'document-text',
                    'routes' => [
                        'list' => '/accounting/journal-entries',
                        'create' => '/accounting/journal-entries/create',
                        'edit' => '/accounting/journal-entries/{id}/edit',
                        'view' => '/accounting/journal-entries/{id}',
                    ],
                ],
                'invoices' => [
                    'name' => 'Invoices',
                    'singular' => 'Invoice',
                    'icon' => 'document-duplicate',
                    'routes' => [
                        'list' => '/accounting/invoices',
                        'create' => '/accounting/invoices/create',
                        'edit' => '/accounting/invoices/{id}/edit',
                        'view' => '/accounting/invoices/{id}',
                    ],
                ],
                'payments' => [
                    'name' => 'Payments',
                    'singular' => 'Payment',
                    'icon' => 'cash',
                    'routes' => [
                        'list' => '/accounting/payments',
                        'create' => '/accounting/payments/create',
                        'edit' => '/accounting/payments/{id}/edit',
                        'view' => '/accounting/payments/{id}',
                    ],
                ],
            ],
            'features' => [
                'double_entry' => true,
                'multi_currency' => true,
                'tax_management' => true,
                'bank_reconciliation' => true,
                'financial_reports' => ['balance_sheet', 'income_statement', 'cash_flow', 'trial_balance'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions(): array
    {
        return [
            'accounting.accounts.view',
            'accounting.accounts.create',
            'accounting.accounts.update',
            'accounting.accounts.delete',
            'accounting.journal-entries.view',
            'accounting.journal-entries.create',
            'accounting.journal-entries.update',
            'accounting.journal-entries.delete',
            'accounting.invoices.view',
            'accounting.invoices.create',
            'accounting.invoices.update',
            'accounting.invoices.delete',
            'accounting.payments.view',
            'accounting.payments.create',
            'accounting.payments.update',
            'accounting.payments.delete',
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
                'path' => '/api/accounting/accounts',
                'name' => 'accounting.accounts.index',
                'permission' => 'accounting.accounts.view',
            ],
            [
                'method' => 'POST',
                'path' => '/api/accounting/accounts',
                'name' => 'accounting.accounts.store',
                'permission' => 'accounting.accounts.create',
            ],
            [
                'method' => 'GET',
                'path' => '/api/accounting/journal-entries',
                'name' => 'accounting.journal-entries.index',
                'permission' => 'accounting.journal-entries.view',
            ],
            [
                'method' => 'POST',
                'path' => '/api/accounting/journal-entries',
                'name' => 'accounting.journal-entries.store',
                'permission' => 'accounting.journal-entries.create',
            ],
        ];
    }

    public function provides(): array
    {
        return [
            \Modules\Accounting\Repositories\AccountRepository::class,
            \Modules\Accounting\Repositories\JournalEntryRepository::class,
            \Modules\Accounting\Repositories\InvoiceRepository::class,
            \Modules\Accounting\Repositories\PaymentRepository::class,
            \Modules\Accounting\Services\AccountService::class,
            \Modules\Accounting\Services\JournalEntryService::class,
            \Modules\Accounting\Services\InvoiceService::class,
            \Modules\Accounting\Services\PaymentService::class,
        ];
    }
}
