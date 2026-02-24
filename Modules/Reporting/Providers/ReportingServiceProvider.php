<?php

namespace Modules\Reporting\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Reporting\Domain\Contracts\DashboardRepositoryInterface;
use Modules\Reporting\Domain\Contracts\ReportRepositoryInterface;
use Modules\Reporting\Infrastructure\Repositories\DashboardRepository;
use Modules\Reporting\Infrastructure\Repositories\ReportRepository;

class ReportingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DashboardRepositoryInterface::class, DashboardRepository::class);
        $this->app->bind(ReportRepositoryInterface::class, ReportRepository::class);
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'reporting');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
    }
}
