<?php

declare(strict_types=1);

namespace Modules\Financial\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Financial\Application\Contracts\AccountServiceInterface;
use Modules\Financial\Application\Contracts\BankAccountServiceInterface;
use Modules\Financial\Application\Contracts\FinancialReportingServiceInterface;
use Modules\Financial\Application\Contracts\FiscalYearServiceInterface;
use Modules\Financial\Application\Contracts\JournalEntryServiceInterface;
use Modules\Financial\Application\Services\AccountService;
use Modules\Financial\Application\Services\BankAccountService;
use Modules\Financial\Application\Services\FinancialReportingService;
use Modules\Financial\Application\Services\FiscalYearService;
use Modules\Financial\Application\Services\JournalEntryService;
use Modules\Financial\Domain\Contracts\Repositories\AccountRepositoryInterface;
use Modules\Financial\Domain\Contracts\Repositories\BankAccountRepositoryInterface;
use Modules\Financial\Domain\Contracts\Repositories\BankTransactionRepositoryInterface;
use Modules\Financial\Domain\Contracts\Repositories\FiscalYearRepositoryInterface;
use Modules\Financial\Domain\Contracts\Repositories\JournalEntryLineRepositoryInterface;
use Modules\Financial\Domain\Contracts\Repositories\JournalEntryRepositoryInterface;
use Modules\Financial\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\Financial\Infrastructure\Persistence\Eloquent\Models\BankAccountModel;
use Modules\Financial\Infrastructure\Persistence\Eloquent\Models\BankTransactionModel;
use Modules\Financial\Infrastructure\Persistence\Eloquent\Models\FiscalYearModel;
use Modules\Financial\Infrastructure\Persistence\Eloquent\Models\JournalEntryLineModel;
use Modules\Financial\Infrastructure\Persistence\Eloquent\Models\JournalEntryModel;
use Modules\Financial\Infrastructure\Persistence\Eloquent\Repositories\EloquentAccountRepository;
use Modules\Financial\Infrastructure\Persistence\Eloquent\Repositories\EloquentBankAccountRepository;
use Modules\Financial\Infrastructure\Persistence\Eloquent\Repositories\EloquentBankTransactionRepository;
use Modules\Financial\Infrastructure\Persistence\Eloquent\Repositories\EloquentFiscalYearRepository;
use Modules\Financial\Infrastructure\Persistence\Eloquent\Repositories\EloquentJournalEntryLineRepository;
use Modules\Financial\Infrastructure\Persistence\Eloquent\Repositories\EloquentJournalEntryRepository;

class FinancialServiceProvider extends ServiceProvider
{
    /**
     * Register Financial module bindings.
     */
    public function register(): void
    {
        // Repositories
        $this->app->bind(AccountRepositoryInterface::class, function ($app) {
            return new EloquentAccountRepository($app->make(AccountModel::class));
        });

        $this->app->bind(FiscalYearRepositoryInterface::class, function ($app) {
            return new EloquentFiscalYearRepository($app->make(FiscalYearModel::class));
        });

        $this->app->bind(JournalEntryRepositoryInterface::class, function ($app) {
            return new EloquentJournalEntryRepository($app->make(JournalEntryModel::class));
        });

        $this->app->bind(JournalEntryLineRepositoryInterface::class, function ($app) {
            return new EloquentJournalEntryLineRepository($app->make(JournalEntryLineModel::class));
        });

        $this->app->bind(BankAccountRepositoryInterface::class, function ($app) {
            return new EloquentBankAccountRepository($app->make(BankAccountModel::class));
        });

        $this->app->bind(BankTransactionRepositoryInterface::class, function ($app) {
            return new EloquentBankTransactionRepository($app->make(BankTransactionModel::class));
        });

        // Services
        $this->app->bind(AccountServiceInterface::class, function ($app) {
            return new AccountService($app->make(AccountRepositoryInterface::class));
        });

        $this->app->bind(FiscalYearServiceInterface::class, function ($app) {
            return new FiscalYearService($app->make(FiscalYearRepositoryInterface::class));
        });

        $this->app->bind(JournalEntryServiceInterface::class, function ($app) {
            return new JournalEntryService(
                $app->make(JournalEntryRepositoryInterface::class),
                $app->make(JournalEntryLineRepositoryInterface::class),
            );
        });

        $this->app->bind(BankAccountServiceInterface::class, function ($app) {
            return new BankAccountService($app->make(BankAccountRepositoryInterface::class));
        });

        $this->app->bind(FinancialReportingServiceInterface::class, function ($app) {
            return new FinancialReportingService(
                $app->make(AccountRepositoryInterface::class),
                $app->make(JournalEntryLineRepositoryInterface::class),
            );
        });
    }

    /**
     * Boot the Financial service provider.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
