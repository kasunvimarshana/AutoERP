<?php

declare(strict_types=1);

namespace Modules\Reporting\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Reporting\Contracts\ReportDataProvider;
use Modules\Reporting\Services\EloquentReportDataProvider;

final class ReportingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/reporting.php', 'reporting');
        $this->app->bind(ReportDataProvider::class, EloquentReportDataProvider::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
    }
}
