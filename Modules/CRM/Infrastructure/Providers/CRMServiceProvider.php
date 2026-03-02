<?php

declare(strict_types=1);

namespace Modules\CRM\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\CRM\Domain\Contracts\CRMRepositoryContract;
use Modules\CRM\Domain\Contracts\CrmLeadRepositoryContract;
use Modules\CRM\Infrastructure\Repositories\CRMRepository;
use Modules\CRM\Infrastructure\Repositories\CrmLeadRepository;

/**
 * CRM module service provider.
 *
 * Registers repository bindings, loads migrations and routes.
 */
class CRMServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            CRMRepositoryContract::class,
            CRMRepository::class,
        );

        $this->app->bind(
            CrmLeadRepositoryContract::class,
            CrmLeadRepository::class,
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
