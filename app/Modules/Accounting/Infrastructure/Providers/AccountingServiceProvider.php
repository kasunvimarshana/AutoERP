<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Accounting\Application\Contracts\BankAccountServiceInterface;
use Modules\Accounting\Application\Contracts\BudgetServiceInterface;
use Modules\Accounting\Application\Contracts\BulkReclassifyTransactionsServiceInterface;
use Modules\Accounting\Application\Contracts\CategorizeTransactionServiceInterface;
use Modules\Accounting\Application\Contracts\GenerateFinancialReportServiceInterface;
use Modules\Accounting\Application\Contracts\ImportBankTransactionsServiceInterface;
use Modules\Accounting\Application\Contracts\PostJournalEntryServiceInterface;
use Modules\Accounting\Application\Services\BankAccountService;
use Modules\Accounting\Application\Services\BudgetService;
use Modules\Accounting\Application\Services\BulkReclassifyTransactionsService;
use Modules\Accounting\Application\Services\CategorizeTransactionService;
use Modules\Accounting\Application\Services\GenerateFinancialReportService;
use Modules\Accounting\Application\Services\ImportBankTransactionsService;
use Modules\Accounting\Application\Services\PostJournalEntryService;
use Modules\Accounting\Domain\RepositoryInterfaces\AccountRepositoryInterface;
use Modules\Accounting\Domain\RepositoryInterfaces\BankAccountRepositoryInterface;
use Modules\Accounting\Domain\RepositoryInterfaces\BankTransactionRepositoryInterface;
use Modules\Accounting\Domain\RepositoryInterfaces\BudgetRepositoryInterface;
use Modules\Accounting\Domain\RepositoryInterfaces\ExpenseCategoryRepositoryInterface;
use Modules\Accounting\Domain\RepositoryInterfaces\JournalEntryRepositoryInterface;
use Modules\Accounting\Domain\RepositoryInterfaces\TransactionRuleRepositoryInterface;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Models\BankAccountModel;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Models\BankTransactionModel;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Models\BudgetModel;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Models\ExpenseCategoryModel;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Models\JournalEntryLineModel;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Models\JournalEntryModel;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Models\TransactionRuleModel;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Repositories\EloquentAccountRepository;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Repositories\EloquentBankAccountRepository;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Repositories\EloquentBankTransactionRepository;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Repositories\EloquentBudgetRepository;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Repositories\EloquentExpenseCategoryRepository;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Repositories\EloquentJournalEntryRepository;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Repositories\EloquentTransactionRuleRepository;

class AccountingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── Original repository bindings ─────────────────────────────────────
        $this->app->bind(AccountRepositoryInterface::class, fn($app) =>
            new EloquentAccountRepository($app->make(AccountModel::class))
        );
        $this->app->bind(JournalEntryRepositoryInterface::class, fn($app) =>
            new EloquentJournalEntryRepository(
                $app->make(JournalEntryModel::class),
                $app->make(JournalEntryLineModel::class)
            )
        );
        $this->app->bind(PostJournalEntryServiceInterface::class, fn($app) =>
            new PostJournalEntryService(
                $app->make(JournalEntryRepositoryInterface::class),
                $app->make(AccountRepositoryInterface::class)
            )
        );

        // ── New repository bindings ───────────────────────────────────────────
        $this->app->bind(BankAccountRepositoryInterface::class, fn($app) =>
            new EloquentBankAccountRepository($app->make(BankAccountModel::class))
        );
        $this->app->bind(BankTransactionRepositoryInterface::class, fn($app) =>
            new EloquentBankTransactionRepository($app->make(BankTransactionModel::class))
        );
        $this->app->bind(ExpenseCategoryRepositoryInterface::class, fn($app) =>
            new EloquentExpenseCategoryRepository($app->make(ExpenseCategoryModel::class))
        );
        $this->app->bind(TransactionRuleRepositoryInterface::class, fn($app) =>
            new EloquentTransactionRuleRepository($app->make(TransactionRuleModel::class))
        );
        $this->app->bind(BudgetRepositoryInterface::class, fn($app) =>
            new EloquentBudgetRepository($app->make(BudgetModel::class))
        );

        // ── New service bindings ──────────────────────────────────────────────
        $this->app->bind(BankAccountServiceInterface::class, fn($app) =>
            new BankAccountService($app->make(BankAccountRepositoryInterface::class))
        );
        $this->app->bind(ImportBankTransactionsServiceInterface::class, fn($app) =>
            new ImportBankTransactionsService(
                $app->make(BankAccountRepositoryInterface::class),
                $app->make(BankTransactionRepositoryInterface::class)
            )
        );
        $this->app->bind(CategorizeTransactionServiceInterface::class, fn($app) =>
            new CategorizeTransactionService(
                $app->make(BankTransactionRepositoryInterface::class),
                $app->make(TransactionRuleRepositoryInterface::class)
            )
        );
        $this->app->bind(BulkReclassifyTransactionsServiceInterface::class, fn($app) =>
            new BulkReclassifyTransactionsService(
                $app->make(BankTransactionRepositoryInterface::class)
            )
        );
        $this->app->bind(GenerateFinancialReportServiceInterface::class, fn($app) =>
            new GenerateFinancialReportService(
                $app->make(AccountRepositoryInterface::class)
            )
        );
        $this->app->bind(BudgetServiceInterface::class, fn($app) =>
            new BudgetService($app->make(BudgetRepositoryInterface::class))
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
