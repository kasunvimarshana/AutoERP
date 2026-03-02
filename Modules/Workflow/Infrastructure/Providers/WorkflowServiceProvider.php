<?php

declare(strict_types=1);

namespace Modules\Workflow\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Workflow\Domain\Contracts\WorkflowRepositoryContract;
use Modules\Workflow\Infrastructure\Repositories\WorkflowRepository;

/**
 * Workflow module service provider.
 *
 * Registers repository bindings, loads migrations and routes.
 */
class WorkflowServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            WorkflowRepositoryContract::class,
            WorkflowRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(
            __DIR__.'/../Database/Migrations'
        );

        $this->loadRoutesFrom(
            __DIR__.'/../../routes/api.php'
        );
    }
}
