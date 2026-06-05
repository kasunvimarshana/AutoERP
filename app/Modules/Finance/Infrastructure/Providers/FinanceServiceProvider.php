<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Finance\Application\Services\FinancePostingService;
use Modules\Finance\Application\Services\JournalEntryService;
use Modules\Finance\Application\Support\FinancialServiceSupport;

final class FinanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/finance.php', 'finance');
        $this->app->singleton(FinancialServiceSupport::class);
        $this->app->singleton(JournalEntryService::class);
        $this->app->singleton(FinancePostingService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Eloquent/Migrations');
    }
}
