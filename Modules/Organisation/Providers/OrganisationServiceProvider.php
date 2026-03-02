<?php

declare(strict_types=1);

namespace Modules\Organisation\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Organisation\Application\Services\OrganisationService;
use Modules\Organisation\Domain\Contracts\OrganisationRepositoryInterface;
use Modules\Organisation\Infrastructure\Repositories\OrganisationRepository;

class OrganisationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OrganisationRepositoryInterface::class, OrganisationRepository::class);

        $this->app->singleton(OrganisationService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Infrastructure/Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Interfaces/Http/routes.php');
    }
}
