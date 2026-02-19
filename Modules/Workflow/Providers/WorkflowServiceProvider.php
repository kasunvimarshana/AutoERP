<?php

declare(strict_types=1);

namespace Modules\Workflow\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Workflow\Models\Approval;
use Modules\Workflow\Models\Workflow;
use Modules\Workflow\Models\WorkflowInstance;
use Modules\Workflow\Policies\ApprovalPolicy;
use Modules\Workflow\Policies\WorkflowInstancePolicy;
use Modules\Workflow\Policies\WorkflowPolicy;

class WorkflowServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/workflow.php', 'workflow');

        $this->app->singleton(\Modules\Workflow\Repositories\WorkflowRepository::class);
        $this->app->singleton(\Modules\Workflow\Repositories\WorkflowStepRepository::class);
        $this->app->singleton(\Modules\Workflow\Repositories\WorkflowInstanceRepository::class);
        $this->app->singleton(\Modules\Workflow\Repositories\ApprovalRepository::class);

        $this->app->singleton(\Modules\Workflow\Services\WorkflowBuilder::class);
        $this->app->singleton(\Modules\Workflow\Services\WorkflowExecutor::class);
        $this->app->singleton(\Modules\Workflow\Services\WorkflowEngine::class);
        $this->app->singleton(\Modules\Workflow\Services\ApprovalService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        Gate::policy(Workflow::class, WorkflowPolicy::class);
        Gate::policy(WorkflowInstance::class, WorkflowInstancePolicy::class);
        Gate::policy(Approval::class, ApprovalPolicy::class);
    }
}
