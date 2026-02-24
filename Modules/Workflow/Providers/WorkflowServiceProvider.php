<?php

namespace Modules\Workflow\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Workflow\Domain\Contracts\WorkflowHistoryRepositoryInterface;
use Modules\Workflow\Domain\Contracts\WorkflowRepositoryInterface;
use Modules\Workflow\Infrastructure\Repositories\WorkflowHistoryRepository;
use Modules\Workflow\Infrastructure\Repositories\WorkflowRepository;

class WorkflowServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WorkflowRepositoryInterface::class, WorkflowRepository::class);
        $this->app->bind(WorkflowHistoryRepositoryInterface::class, WorkflowHistoryRepository::class);
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'workflow');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
    }
}
