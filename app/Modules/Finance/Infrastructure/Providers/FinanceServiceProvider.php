<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Finance\Application\Contracts\AccountServiceInterface;
use Modules\Finance\Application\Contracts\FinancialReportServiceInterface;
use Modules\Finance\Application\Contracts\JournalEntryServiceInterface;
use Modules\Finance\Application\Contracts\TransactionServiceInterface;
use Modules\Finance\Application\Services\AccountService;
use Modules\Finance\Application\Services\FinancialReportService;
use Modules\Finance\Application\Services\JournalEntryService;
use Modules\Finance\Application\Services\TransactionService;
use Modules\Finance\Domain\RepositoryInterfaces\AccountRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\JournalEntryRepositoryInterface;
use Modules\Finance\Domain\RepositoryInterfaces\TransactionRepositoryInterface;
use Modules\Finance\Infrastructure\Http\Controllers\AccountController;
use Modules\Finance\Infrastructure\Http\Controllers\JournalEntryController;
use Modules\Finance\Infrastructure\Http\Controllers\TransactionController;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\JournalEntryModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\TransactionModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentAccountRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentJournalEntryRepository;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories\EloquentTransactionRepository;

final class FinanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── Repositories ──────────────────────────────────────────────────────
        $this->app->bind(AccountRepositoryInterface::class, static function ($app) {
            return new EloquentAccountRepository($app->make(AccountModel::class));
        });

        $this->app->bind(JournalEntryRepositoryInterface::class, static function ($app) {
            return new EloquentJournalEntryRepository($app->make(JournalEntryModel::class));
        });

        $this->app->bind(TransactionRepositoryInterface::class, static function ($app) {
            return new EloquentTransactionRepository($app->make(TransactionModel::class));
        });

        // ── Application Services ──────────────────────────────────────────────
        $this->app->singleton(AccountServiceInterface::class, static function ($app) {
            return new AccountService(
                $app->make(AccountRepositoryInterface::class),
            );
        });

        $this->app->singleton(JournalEntryServiceInterface::class, static function ($app) {
            return new JournalEntryService(
                $app->make(JournalEntryRepositoryInterface::class),
                $app->make(AccountRepositoryInterface::class),
            );
        });

        $this->app->singleton(TransactionServiceInterface::class, static function ($app) {
            return new TransactionService(
                $app->make(TransactionRepositoryInterface::class),
            );
        });

        $this->app->singleton(FinancialReportServiceInterface::class, FinancialReportService::class);

        $this->mergeConfigFrom(__DIR__ . '/../../config/finance.php', 'finance');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->registerRoutes();

        $this->publishes([
            __DIR__ . '/../../config/finance.php' => config_path('finance.php'),
        ], 'finance-config');

        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'finance-migrations');
    }

    private function registerRoutes(): void
    {
        Route::middleware(['api', 'auth:api'])
            ->prefix('api/finance')
            ->group(static function (): void {
                // Accounts
                Route::get('accounts/tree', [AccountController::class, 'getTree']);
                Route::apiResource('accounts', AccountController::class);

                // Journal Entries
                Route::post('journal-entries/{id}/post', [JournalEntryController::class, 'post']);
                Route::post('journal-entries/{id}/void', [JournalEntryController::class, 'void']);
                Route::apiResource('journal-entries', JournalEntryController::class);

                // Transactions (read + create only)
                Route::get('transactions', [TransactionController::class, 'index']);
                Route::post('transactions', [TransactionController::class, 'store']);
                Route::get('transactions/{id}', [TransactionController::class, 'show']);
            });
    }
}
