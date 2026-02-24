<?php

namespace Modules\Expense\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Expense\Domain\Contracts\ExpenseCategoryRepositoryInterface;
use Modules\Expense\Domain\Contracts\ExpenseClaimLineRepositoryInterface;
use Modules\Expense\Domain\Contracts\ExpenseClaimRepositoryInterface;
use Modules\Expense\Infrastructure\Repositories\ExpenseCategoryRepository;
use Modules\Expense\Infrastructure\Repositories\ExpenseClaimLineRepository;
use Modules\Expense\Infrastructure\Repositories\ExpenseClaimRepository;

class ExpenseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ExpenseCategoryRepositoryInterface::class, ExpenseCategoryRepository::class);
        $this->app->bind(ExpenseClaimRepositoryInterface::class, ExpenseClaimRepository::class);
        $this->app->bind(ExpenseClaimLineRepositoryInterface::class, ExpenseClaimLineRepository::class);
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'expense');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
    }
}
