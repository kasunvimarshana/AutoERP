<?php

declare(strict_types=1);

namespace Modules\Transaction\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Transaction\Application\Contracts\CreateJournalEntryServiceInterface;
use Modules\Transaction\Application\Contracts\CreateTransactionServiceInterface;
use Modules\Transaction\Application\Contracts\DeleteTransactionServiceInterface;
use Modules\Transaction\Application\Contracts\FindJournalEntryServiceInterface;
use Modules\Transaction\Application\Contracts\FindTransactionServiceInterface;
use Modules\Transaction\Application\Contracts\PostJournalEntryServiceInterface;
use Modules\Transaction\Application\Contracts\PostTransactionServiceInterface;
use Modules\Transaction\Application\Contracts\UpdateJournalEntryServiceInterface;
use Modules\Transaction\Application\Contracts\UpdateTransactionServiceInterface;
use Modules\Transaction\Application\Contracts\VoidTransactionServiceInterface;
use Modules\Transaction\Application\Services\CreateJournalEntryService;
use Modules\Transaction\Application\Services\CreateTransactionService;
use Modules\Transaction\Application\Services\DeleteTransactionService;
use Modules\Transaction\Application\Services\FindJournalEntryService;
use Modules\Transaction\Application\Services\FindTransactionService;
use Modules\Transaction\Application\Services\PostJournalEntryService;
use Modules\Transaction\Application\Services\PostTransactionService;
use Modules\Transaction\Application\Services\UpdateJournalEntryService;
use Modules\Transaction\Application\Services\UpdateTransactionService;
use Modules\Transaction\Application\Services\VoidTransactionService;
use Modules\Transaction\Domain\RepositoryInterfaces\JournalEntryRepositoryInterface;
use Modules\Transaction\Domain\RepositoryInterfaces\TransactionRepositoryInterface;
use Modules\Transaction\Infrastructure\Persistence\Eloquent\Models\JournalEntryModel;
use Modules\Transaction\Infrastructure\Persistence\Eloquent\Models\TransactionModel;
use Modules\Transaction\Infrastructure\Persistence\Eloquent\Repositories\EloquentJournalEntryRepository;
use Modules\Transaction\Infrastructure\Persistence\Eloquent\Repositories\EloquentTransactionRepository;

class TransactionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TransactionRepositoryInterface::class, fn ($app) =>
            new EloquentTransactionRepository($app->make(TransactionModel::class)));

        $this->app->bind(JournalEntryRepositoryInterface::class, fn ($app) =>
            new EloquentJournalEntryRepository($app->make(JournalEntryModel::class)));

        $this->app->bind(CreateTransactionServiceInterface::class, fn ($app) =>
            new CreateTransactionService($app->make(TransactionRepositoryInterface::class)));

        $this->app->bind(FindTransactionServiceInterface::class, fn ($app) =>
            new FindTransactionService($app->make(TransactionRepositoryInterface::class)));

        $this->app->bind(UpdateTransactionServiceInterface::class, fn ($app) =>
            new UpdateTransactionService($app->make(TransactionRepositoryInterface::class)));

        $this->app->bind(DeleteTransactionServiceInterface::class, fn ($app) =>
            new DeleteTransactionService($app->make(TransactionRepositoryInterface::class)));

        $this->app->bind(PostTransactionServiceInterface::class, fn ($app) =>
            new PostTransactionService($app->make(TransactionRepositoryInterface::class)));

        $this->app->bind(VoidTransactionServiceInterface::class, fn ($app) =>
            new VoidTransactionService($app->make(TransactionRepositoryInterface::class)));

        $this->app->bind(CreateJournalEntryServiceInterface::class, fn ($app) =>
            new CreateJournalEntryService($app->make(JournalEntryRepositoryInterface::class)));

        $this->app->bind(FindJournalEntryServiceInterface::class, fn ($app) =>
            new FindJournalEntryService($app->make(JournalEntryRepositoryInterface::class)));

        $this->app->bind(UpdateJournalEntryServiceInterface::class, fn ($app) =>
            new UpdateJournalEntryService($app->make(JournalEntryRepositoryInterface::class)));

        $this->app->bind(PostJournalEntryServiceInterface::class, fn ($app) =>
            new PostJournalEntryService($app->make(JournalEntryRepositoryInterface::class)));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        Route::middleware(['api', 'auth:api', 'resolve.tenant'])
            ->prefix('api')
            ->group(function () {
                $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
            });
    }
}
