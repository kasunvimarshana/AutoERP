<?php

declare(strict_types=1);

namespace Modules\Accounting\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Accounting\Application\Handlers\PostJournalEntryHandler;
use Modules\Accounting\Domain\Contracts\AccountRepositoryInterface;
use Modules\Accounting\Domain\Contracts\JournalEntryRepositoryInterface;
use Modules\Accounting\Domain\Contracts\TaxRateRepositoryInterface;
use Modules\Accounting\Infrastructure\Repositories\AccountRepository;
use Modules\Accounting\Infrastructure\Repositories\JournalEntryRepository;
use Modules\Accounting\Infrastructure\Repositories\TaxRateRepository;

class AccountingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AccountRepositoryInterface::class, AccountRepository::class);
        $this->app->bind(JournalEntryRepositoryInterface::class, JournalEntryRepository::class);
        $this->app->bind(TaxRateRepositoryInterface::class, TaxRateRepository::class);
        $this->app->singleton(PostJournalEntryHandler::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Infrastructure/Database/Migrations');
    }
}

