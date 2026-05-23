<?php

namespace Modules\Finance\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Finance\Application\Repositories\AccountRepositoryInterface;
use Modules\Finance\Application\Repositories\ApTransactionRepositoryInterface;
use Modules\Finance\Application\Repositories\ArTransactionRepositoryInterface;
use Modules\Finance\Application\Repositories\BankAccountRepositoryInterface;
use Modules\Finance\Application\Repositories\BankCategoryRuleRepositoryInterface;
use Modules\Finance\Application\Repositories\BankReconciliationRepositoryInterface;
use Modules\Finance\Application\Repositories\BankTransactionRepositoryInterface;
use Modules\Finance\Application\Repositories\BudgetLineRepositoryInterface;
use Modules\Finance\Application\Repositories\BudgetRepositoryInterface;
use Modules\Finance\Application\Repositories\CostCenterRepositoryInterface;
use Modules\Finance\Application\Repositories\FiscalPeriodRepositoryInterface;
use Modules\Finance\Application\Repositories\FiscalYearRepositoryInterface;
use Modules\Finance\Application\Repositories\JournalEntryLineRepositoryInterface;
use Modules\Finance\Application\Repositories\JournalEntryRepositoryInterface;
use Modules\Finance\Application\Repositories\PaymentTermRepositoryInterface;
use Modules\Finance\Application\Repositories\TaxGroupRepositoryInterface;
use Modules\Finance\Application\Repositories\TaxRateRepositoryInterface;
use Modules\Finance\Application\Repositories\TaxRuleRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentAccountRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentApTransactionRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentArTransactionRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentBankAccountRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentBankCategoryRuleRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentBankReconciliationRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentBankTransactionRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentBudgetLineRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentBudgetRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentCostCenterRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentFiscalPeriodRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentFiscalYearRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentJournalEntryLineRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentJournalEntryRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentPaymentTermRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentTaxGroupRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentTaxRateRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentTaxRuleRepository;

class FinanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/finance.php', 'finance');

        foreach ([
            AccountRepositoryInterface::class => EloquentAccountRepository::class,
            ApTransactionRepositoryInterface::class => EloquentApTransactionRepository::class,
            ArTransactionRepositoryInterface::class => EloquentArTransactionRepository::class,
            BankAccountRepositoryInterface::class => EloquentBankAccountRepository::class,
            BankCategoryRuleRepositoryInterface::class => EloquentBankCategoryRuleRepository::class,
            BankReconciliationRepositoryInterface::class => EloquentBankReconciliationRepository::class,
            BankTransactionRepositoryInterface::class => EloquentBankTransactionRepository::class,
            BudgetLineRepositoryInterface::class => EloquentBudgetLineRepository::class,
            BudgetRepositoryInterface::class => EloquentBudgetRepository::class,
            CostCenterRepositoryInterface::class => EloquentCostCenterRepository::class,
            FiscalPeriodRepositoryInterface::class => EloquentFiscalPeriodRepository::class,
            FiscalYearRepositoryInterface::class => EloquentFiscalYearRepository::class,
            JournalEntryLineRepositoryInterface::class => EloquentJournalEntryLineRepository::class,
            JournalEntryRepositoryInterface::class => EloquentJournalEntryRepository::class,
            PaymentTermRepositoryInterface::class => EloquentPaymentTermRepository::class,
            TaxGroupRepositoryInterface::class => EloquentTaxGroupRepository::class,
            TaxRateRepositoryInterface::class => EloquentTaxRateRepository::class,
            TaxRuleRepositoryInterface::class => EloquentTaxRuleRepository::class,
        ] as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../../Infrastructure/Persistence/Eloquent/Migrations');
    }
}
