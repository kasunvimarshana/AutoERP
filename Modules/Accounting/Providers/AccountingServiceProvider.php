<?php

declare(strict_types=1);

namespace Modules\Accounting\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Policies\AccountPolicy;
use Modules\Accounting\Policies\FiscalPeriodPolicy;
use Modules\Accounting\Policies\JournalEntryPolicy;

class AccountingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register module configuration
        $this->mergeConfigFrom(__DIR__.'/../Config/accounting.php', 'accounting');

        // Register repositories
        $this->app->singleton(\Modules\Accounting\Repositories\AccountRepository::class);
        $this->app->singleton(\Modules\Accounting\Repositories\JournalEntryRepository::class);
        $this->app->singleton(\Modules\Accounting\Repositories\FiscalPeriodRepository::class);

        // Register services
        $this->app->singleton(\Modules\Accounting\Services\AccountingService::class);
        $this->app->singleton(\Modules\Accounting\Services\ChartOfAccountsService::class);
        $this->app->singleton(\Modules\Accounting\Services\GeneralLedgerService::class);
        $this->app->singleton(\Modules\Accounting\Services\TrialBalanceService::class);
        $this->app->singleton(\Modules\Accounting\Services\FinancialStatementService::class);
    }

    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        // Register policies
        Gate::policy(Account::class, AccountPolicy::class);
        Gate::policy(JournalEntry::class, JournalEntryPolicy::class);
        Gate::policy(FiscalPeriod::class, FiscalPeriodPolicy::class);
    }
}
