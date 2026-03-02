<?php

declare(strict_types=1);

namespace Modules\Organisation\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Organisation\Domain\Contracts\BranchRepositoryContract;
use Modules\Organisation\Domain\Contracts\DepartmentRepositoryContract;
use Modules\Organisation\Domain\Contracts\LocationRepositoryContract;
use Modules\Organisation\Domain\Contracts\OrganisationRepositoryContract;
use Modules\Organisation\Infrastructure\Repositories\BranchRepository;
use Modules\Organisation\Infrastructure\Repositories\DepartmentRepository;
use Modules\Organisation\Infrastructure\Repositories\LocationRepository;
use Modules\Organisation\Infrastructure\Repositories\OrganisationRepository;

/**
 * Organisation module service provider.
 *
 * Registers repository bindings, loads migrations and routes.
 */
class OrganisationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            OrganisationRepositoryContract::class,
            OrganisationRepository::class,
        );

        $this->app->bind(
            BranchRepositoryContract::class,
            BranchRepository::class,
        );

        $this->app->bind(
            LocationRepositoryContract::class,
            LocationRepository::class,
        );

        $this->app->bind(
            DepartmentRepositoryContract::class,
            DepartmentRepository::class,
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
