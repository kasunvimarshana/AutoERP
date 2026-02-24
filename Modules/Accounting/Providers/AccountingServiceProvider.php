<?php

namespace Modules\Accounting\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Accounting\Application\Listeners\HandleSalesOrderConfirmedListener;
use Modules\Accounting\Application\Listeners\HandleGoodsReceivedListener;
use Modules\Accounting\Application\Listeners\HandleExpenseClaimReimbursedListener;
use Modules\Accounting\Application\Listeners\HandleSubscriptionRenewedListener;
use Modules\Accounting\Application\Listeners\HandlePayrollRunCompletedListener;
use Modules\Accounting\Application\Listeners\HandleAssetDepreciatedListener;
use Modules\Accounting\Domain\Contracts\AccountRepositoryInterface;
use Modules\Accounting\Domain\Contracts\JournalEntryRepositoryInterface;
use Modules\Accounting\Domain\Contracts\InvoiceRepositoryInterface;
use Modules\Accounting\Domain\Contracts\BankAccountRepositoryInterface;
use Modules\Accounting\Domain\Contracts\BankTransactionRepositoryInterface;
use Modules\Accounting\Domain\Contracts\AccountingPeriodRepositoryInterface;
use Modules\Accounting\Infrastructure\Repositories\AccountRepository;
use Modules\Accounting\Infrastructure\Repositories\JournalEntryRepository;
use Modules\Accounting\Infrastructure\Repositories\InvoiceRepository;
use Modules\Accounting\Infrastructure\Repositories\BankAccountRepository;
use Modules\Accounting\Infrastructure\Repositories\BankTransactionRepository;
use Modules\Accounting\Infrastructure\Repositories\AccountingPeriodRepository;
use Modules\Expense\Domain\Events\ExpenseClaimReimbursed;
use Modules\Sales\Domain\Events\OrderConfirmed;
use Modules\Purchase\Domain\Events\GoodsReceived;
use Modules\SubscriptionBilling\Domain\Events\SubscriptionRenewed;
use Modules\HR\Domain\Events\PayrollRunCompleted;
use Modules\AssetManagement\Domain\Events\AssetDepreciated;

class AccountingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AccountRepositoryInterface::class, AccountRepository::class);
        $this->app->bind(JournalEntryRepositoryInterface::class, JournalEntryRepository::class);
        $this->app->bind(InvoiceRepositoryInterface::class, InvoiceRepository::class);
        $this->app->bind(BankAccountRepositoryInterface::class, BankAccountRepository::class);
        $this->app->bind(BankTransactionRepositoryInterface::class, BankTransactionRepository::class);
        $this->app->bind(AccountingPeriodRepositoryInterface::class, AccountingPeriodRepository::class);
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'accounting');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');

        Event::listen(OrderConfirmed::class, HandleSalesOrderConfirmedListener::class);
        Event::listen(GoodsReceived::class, HandleGoodsReceivedListener::class);
        Event::listen(ExpenseClaimReimbursed::class, HandleExpenseClaimReimbursedListener::class);
        Event::listen(SubscriptionRenewed::class, HandleSubscriptionRenewedListener::class);
        Event::listen(PayrollRunCompleted::class, HandlePayrollRunCompletedListener::class);
        Event::listen(AssetDepreciated::class, HandleAssetDepreciatedListener::class);
    }
}
