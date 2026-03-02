<?php

declare(strict_types=1);

namespace Modules\Accounting\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Accounting\Domain\Contracts\AccountRepositoryContract;
use Modules\Accounting\Domain\Contracts\FiscalPeriodRepositoryContract;
use Modules\Accounting\Domain\Contracts\JournalEntryRepositoryContract;
use Modules\Accounting\Infrastructure\Repositories\AccountRepository;
use Modules\Accounting\Infrastructure\Repositories\FiscalPeriodRepository;
use Modules\Accounting\Infrastructure\Repositories\JournalEntryRepository;

/**
 * Accounting module service provider.
 *
 * Registers repository bindings, loads migrations and routes.
 */
class AccountingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AccountRepositoryContract::class,
            AccountRepository::class,
        );

        $this->app->bind(
            JournalEntryRepositoryContract::class,
            JournalEntryRepository::class,
        );

        $this->app->bind(
            FiscalPeriodRepositoryContract::class,
            FiscalPeriodRepository::class,
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
