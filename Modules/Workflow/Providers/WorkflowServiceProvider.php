<?php

declare(strict_types=1);

namespace Modules\Workflow\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Workflow\Application\Services\WorkflowDefinitionService;
use Modules\Workflow\Application\Services\WorkflowInstanceService;
use Modules\Workflow\Domain\Contracts\WorkflowDefinitionRepositoryInterface;
use Modules\Workflow\Domain\Contracts\WorkflowInstanceRepositoryInterface;
use Modules\Workflow\Infrastructure\Repositories\WorkflowDefinitionRepository;
use Modules\Workflow\Infrastructure\Repositories\WorkflowInstanceRepository;

class WorkflowServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            WorkflowDefinitionRepositoryInterface::class,
            WorkflowDefinitionRepository::class
        );

        $this->app->bind(
            WorkflowInstanceRepositoryInterface::class,
            WorkflowInstanceRepository::class
        );

        $this->app->singleton(WorkflowDefinitionService::class);
        $this->app->singleton(WorkflowInstanceService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Infrastructure/Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Interfaces/Http/routes.php');
    }
}
