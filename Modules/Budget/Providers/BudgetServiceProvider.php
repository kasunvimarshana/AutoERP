<?php

namespace Modules\Budget\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Budget\Domain\Contracts\BudgetLineRepositoryInterface;
use Modules\Budget\Domain\Contracts\BudgetRepositoryInterface;
use Modules\Budget\Infrastructure\Repositories\BudgetLineRepository;
use Modules\Budget\Infrastructure\Repositories\BudgetRepository;

class BudgetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BudgetRepositoryInterface::class, BudgetRepository::class);
        $this->app->bind(BudgetLineRepositoryInterface::class, BudgetLineRepository::class);
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'budget');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
    }
}
