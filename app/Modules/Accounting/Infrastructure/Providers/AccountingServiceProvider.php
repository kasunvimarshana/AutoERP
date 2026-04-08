<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Providers;
use Illuminate\Support\ServiceProvider;
use Modules\Accounting\Application\Contracts\AccountServiceInterface;
use Modules\Accounting\Application\Contracts\BankAccountServiceInterface;
use Modules\Accounting\Application\Contracts\BankTransactionServiceInterface;
use Modules\Accounting\Application\Contracts\BudgetServiceInterface;
use Modules\Accounting\Application\Contracts\BulkReclassifyTransactionsServiceInterface;
use Modules\Accounting\Application\Contracts\CategorizeTransactionServiceInterface;
use Modules\Accounting\Application\Contracts\GenerateFinancialReportServiceInterface;
use Modules\Accounting\Application\Contracts\ImportBankTransactionsServiceInterface;
use Modules\Accounting\Application\Contracts\JournalEntryServiceInterface;
use Modules\Accounting\Application\Contracts\TransactionRuleServiceInterface;
use Modules\Accounting\Application\Services\AccountService;
use Modules\Accounting\Application\Services\BankAccountService;
use Modules\Accounting\Application\Services\BankTransactionService;
use Modules\Accounting\Application\Services\BudgetService;
use Modules\Accounting\Application\Services\BulkReclassifyTransactionsService;
use Modules\Accounting\Application\Services\CategorizeTransactionService;
use Modules\Accounting\Application\Services\GenerateFinancialReportService;
use Modules\Accounting\Application\Services\ImportBankTransactionsService;
use Modules\Accounting\Application\Services\JournalEntryService;
use Modules\Accounting\Application\Services\TransactionRuleService;
use Modules\Accounting\Domain\RepositoryInterfaces\AccountRepositoryInterface;
use Modules\Accounting\Domain\RepositoryInterfaces\BankAccountRepositoryInterface;
use Modules\Accounting\Domain\RepositoryInterfaces\BankTransactionRepositoryInterface;
use Modules\Accounting\Domain\RepositoryInterfaces\BudgetRepositoryInterface;
use Modules\Accounting\Domain\RepositoryInterfaces\JournalEntryLineRepositoryInterface;
use Modules\Accounting\Domain\RepositoryInterfaces\JournalEntryRepositoryInterface;
use Modules\Accounting\Domain\RepositoryInterfaces\TransactionRuleRepositoryInterface;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Repositories\EloquentAccountRepository;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Repositories\EloquentBankAccountRepository;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Repositories\EloquentBankTransactionRepository;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Repositories\EloquentBudgetRepository;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Repositories\EloquentJournalEntryLineRepository;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Repositories\EloquentJournalEntryRepository;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Repositories\EloquentTransactionRuleRepository;
class AccountingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AccountRepositoryInterface::class, EloquentAccountRepository::class);
        $this->app->bind(BankAccountRepositoryInterface::class, EloquentBankAccountRepository::class);
        $this->app->bind(BankTransactionRepositoryInterface::class, EloquentBankTransactionRepository::class);
        $this->app->bind(BudgetRepositoryInterface::class, EloquentBudgetRepository::class);
        $this->app->bind(JournalEntryRepositoryInterface::class, EloquentJournalEntryRepository::class);
        $this->app->bind(JournalEntryLineRepositoryInterface::class, EloquentJournalEntryLineRepository::class);
        $this->app->bind(TransactionRuleRepositoryInterface::class, EloquentTransactionRuleRepository::class);
        $this->app->bind(AccountServiceInterface::class, AccountService::class);
        $this->app->bind(BankAccountServiceInterface::class, BankAccountService::class);
        $this->app->bind(BankTransactionServiceInterface::class, BankTransactionService::class);
        $this->app->bind(BudgetServiceInterface::class, BudgetService::class);
        $this->app->bind(JournalEntryServiceInterface::class, JournalEntryService::class);
        $this->app->bind(TransactionRuleServiceInterface::class, TransactionRuleService::class);
        $this->app->bind(CategorizeTransactionServiceInterface::class, CategorizeTransactionService::class);
        $this->app->bind(ImportBankTransactionsServiceInterface::class, ImportBankTransactionsService::class);
        $this->app->bind(BulkReclassifyTransactionsServiceInterface::class, BulkReclassifyTransactionsService::class);
        $this->app->bind(GenerateFinancialReportServiceInterface::class, GenerateFinancialReportService::class);
    }
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
