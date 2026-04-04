<?php
namespace Modules\Accounting\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Accounting\Application\Contracts\CreateAccountServiceInterface;
use Modules\Accounting\Application\Contracts\CreateJournalEntryServiceInterface;
use Modules\Accounting\Application\Contracts\DeleteAccountServiceInterface;
use Modules\Accounting\Application\Contracts\PostJournalEntryServiceInterface;
use Modules\Accounting\Application\Contracts\ProcessPaymentServiceInterface;
use Modules\Accounting\Application\Contracts\ProcessRefundServiceInterface;
use Modules\Accounting\Application\Contracts\ReverseJournalEntryServiceInterface;
use Modules\Accounting\Application\Contracts\UpdateAccountServiceInterface;
use Modules\Accounting\Application\Services\CreateAccountService;
use Modules\Accounting\Application\Services\CreateJournalEntryService;
use Modules\Accounting\Application\Services\DeleteAccountService;
use Modules\Accounting\Application\Services\PostJournalEntryService;
use Modules\Accounting\Application\Services\ProcessPaymentService;
use Modules\Accounting\Application\Services\ProcessRefundService;
use Modules\Accounting\Application\Services\ReverseJournalEntryService;
use Modules\Accounting\Application\Services\UpdateAccountService;
use Modules\Accounting\Domain\Repositories\AccountRepositoryInterface;
use Modules\Accounting\Domain\Repositories\JournalEntryRepositoryInterface;
use Modules\Accounting\Domain\Repositories\JournalLineRepositoryInterface;
use Modules\Accounting\Domain\Repositories\PaymentRepositoryInterface;
use Modules\Accounting\Domain\Repositories\RefundRepositoryInterface;
use Modules\Accounting\Infrastructure\Persistence\Repositories\EloquentAccountRepository;
use Modules\Accounting\Infrastructure\Persistence\Repositories\EloquentJournalEntryRepository;
use Modules\Accounting\Infrastructure\Persistence\Repositories\EloquentJournalLineRepository;
use Modules\Accounting\Infrastructure\Persistence\Repositories\EloquentPaymentRepository;
use Modules\Accounting\Infrastructure\Persistence\Repositories\EloquentRefundRepository;

class AccountingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AccountRepositoryInterface::class, EloquentAccountRepository::class);
        $this->app->bind(JournalEntryRepositoryInterface::class, EloquentJournalEntryRepository::class);
        $this->app->bind(JournalLineRepositoryInterface::class, EloquentJournalLineRepository::class);
        $this->app->bind(PaymentRepositoryInterface::class, EloquentPaymentRepository::class);
        $this->app->bind(RefundRepositoryInterface::class, EloquentRefundRepository::class);

        $this->app->bind(CreateAccountServiceInterface::class, CreateAccountService::class);
        $this->app->bind(UpdateAccountServiceInterface::class, UpdateAccountService::class);
        $this->app->bind(DeleteAccountServiceInterface::class, DeleteAccountService::class);
        $this->app->bind(CreateJournalEntryServiceInterface::class, CreateJournalEntryService::class);
        $this->app->bind(PostJournalEntryServiceInterface::class, PostJournalEntryService::class);
        $this->app->bind(ReverseJournalEntryServiceInterface::class, ReverseJournalEntryService::class);
        $this->app->bind(ProcessPaymentServiceInterface::class, ProcessPaymentService::class);
        $this->app->bind(ProcessRefundServiceInterface::class, ProcessRefundService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
