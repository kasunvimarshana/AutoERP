<?php

namespace Modules\ProjectManagement\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\ProjectManagement\Domain\Contracts\MilestoneRepositoryInterface;
use Modules\ProjectManagement\Domain\Contracts\ProjectRepositoryInterface;
use Modules\ProjectManagement\Domain\Contracts\TaskRepositoryInterface;
use Modules\ProjectManagement\Domain\Contracts\TimeEntryRepositoryInterface;
use Modules\ProjectManagement\Infrastructure\Repositories\MilestoneRepository;
use Modules\ProjectManagement\Infrastructure\Repositories\ProjectRepository;
use Modules\ProjectManagement\Infrastructure\Repositories\TaskRepository;
use Modules\ProjectManagement\Infrastructure\Repositories\TimeEntryRepository;

class ProjectManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProjectRepositoryInterface::class, ProjectRepository::class);
        $this->app->bind(TaskRepositoryInterface::class, TaskRepository::class);
        $this->app->bind(MilestoneRepositoryInterface::class, MilestoneRepository::class);
        $this->app->bind(TimeEntryRepositoryInterface::class, TimeEntryRepository::class);
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'project_management');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
    }
}
