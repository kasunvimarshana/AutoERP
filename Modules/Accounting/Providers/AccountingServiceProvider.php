<?php

declare(strict_types=1);

namespace Modules\Accounting\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Accounting\Repositories\AccountRepository;
use Modules\Accounting\Repositories\Contracts\AccountRepositoryInterface;
use Modules\Accounting\Repositories\Contracts\FiscalPeriodRepositoryInterface;
use Modules\Accounting\Repositories\Contracts\InvoiceLineRepositoryInterface;
use Modules\Accounting\Repositories\Contracts\InvoiceRepositoryInterface;
use Modules\Accounting\Repositories\Contracts\JournalEntryLineRepositoryInterface;
use Modules\Accounting\Repositories\Contracts\JournalEntryRepositoryInterface;
use Modules\Accounting\Repositories\Contracts\PaymentRepositoryInterface;
use Modules\Accounting\Repositories\FiscalPeriodRepository;
use Modules\Accounting\Repositories\InvoiceLineRepository;
use Modules\Accounting\Repositories\InvoiceRepository;
use Modules\Accounting\Repositories\JournalEntryLineRepository;
use Modules\Accounting\Repositories\JournalEntryRepository;
use Modules\Accounting\Repositories\PaymentRepository;

/**
 * Accounting Service Provider
 *
 * Registers module services, repositories, and routes.
 */
class AccountingServiceProvider extends ServiceProvider
{
    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerConfig();
        $this->registerRoutes();
        $this->registerMigrations();
        $this->registerTranslations();
        $this->registerViews();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->registerRepositories();
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            module_path('Accounting', 'Config/config.php') => config_path('accounting.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path('Accounting', 'Config/config.php'),
            'accounting'
        );
    }

    /**
     * Register routes.
     */
    protected function registerRoutes(): void
    {
        Route::middleware('api')
            ->prefix('api/accounting')
            ->group(module_path('Accounting', 'Routes/api.php'));
    }

    /**
     * Register migrations.
     */
    protected function registerMigrations(): void
    {
        $this->loadMigrationsFrom(module_path('Accounting', 'Database/Migrations'));
    }

    /**
     * Register translations.
     */
    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/accounting');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'accounting');
        } else {
            $this->loadTranslationsFrom(module_path('Accounting', 'Resources/lang'), 'accounting');
        }
    }

    /**
     * Register views.
     */
    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/accounting');
        $sourcePath = module_path('Accounting', 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', 'accounting-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), 'accounting');
    }

    /**
     * Register repositories.
     */
    protected function registerRepositories(): void
    {
        $this->app->bind(AccountRepositoryInterface::class, AccountRepository::class);
        $this->app->bind(JournalEntryRepositoryInterface::class, JournalEntryRepository::class);
        $this->app->bind(JournalEntryLineRepositoryInterface::class, JournalEntryLineRepository::class);
        $this->app->bind(InvoiceRepositoryInterface::class, InvoiceRepository::class);
        $this->app->bind(InvoiceLineRepositoryInterface::class, InvoiceLineRepository::class);
        $this->app->bind(PaymentRepositoryInterface::class, PaymentRepository::class);
        $this->app->bind(FiscalPeriodRepositoryInterface::class, FiscalPeriodRepository::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * Get publishable view paths.
     *
     * @return array<string>
     */
    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach ($this->app['config']->get('view.paths') as $path) {
            if (is_dir($path.'/modules/accounting')) {
                $paths[] = $path.'/modules/accounting';
            }
        }

        return $paths;
    }
}
