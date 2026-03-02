<?php

declare(strict_types=1);

namespace Modules\Accounting\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Accounting\Application\Services\AccountService;
use Modules\Accounting\Application\Services\JournalEntryService;
use Modules\Accounting\Domain\Contracts\AccountRepositoryInterface;
use Modules\Accounting\Domain\Contracts\JournalEntryRepositoryInterface;
use Modules\Accounting\Infrastructure\Repositories\AccountRepository;
use Modules\Accounting\Infrastructure\Repositories\JournalEntryRepository;

class AccountingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AccountRepositoryInterface::class, AccountRepository::class);
        $this->app->bind(JournalEntryRepositoryInterface::class, JournalEntryRepository::class);

        $this->app->singleton(AccountService::class);
        $this->app->singleton(JournalEntryService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Infrastructure/Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Interfaces/Http/routes.php');
    }
}
