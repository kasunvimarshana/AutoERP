<?php

declare(strict_types=1);

namespace Modules\Transaction\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Transaction\Application\Contracts\TransactionLineServiceInterface;
use Modules\Transaction\Application\Contracts\TransactionServiceInterface;
use Modules\Transaction\Application\Services\TransactionLineService;
use Modules\Transaction\Application\Services\TransactionService;
use Modules\Transaction\Domain\RepositoryInterfaces\TransactionLineRepositoryInterface;
use Modules\Transaction\Domain\RepositoryInterfaces\TransactionRepositoryInterface;
use Modules\Transaction\Infrastructure\Persistence\Eloquent\Repositories\EloquentTransactionLineRepository;
use Modules\Transaction\Infrastructure\Persistence\Eloquent\Repositories\EloquentTransactionRepository;

class TransactionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TransactionRepositoryInterface::class, EloquentTransactionRepository::class);
        $this->app->bind(TransactionLineRepositoryInterface::class, EloquentTransactionLineRepository::class);
        $this->app->bind(TransactionServiceInterface::class, TransactionService::class);
        $this->app->bind(TransactionLineServiceInterface::class, TransactionLineService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
