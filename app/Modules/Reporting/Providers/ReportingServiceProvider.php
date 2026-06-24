<?php

declare(strict_types=1);

namespace Modules\Reporting\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Reporting\Contracts\ReportDataProvider;
use Modules\Reporting\Services\EloquentReportDataProvider;
use Modules\Reporting\Services\ReportingAuthorizationService;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;

final class ReportingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/reporting.php', 'reporting');
        $this->app->bind(ReportDataProvider::class, EloquentReportDataProvider::class);
    }

    public function boot(): void
    {
        $this->app->make(PermissionDefinitionRegistryInterface::class)
            ->register('reporting', ReportingAuthorizationService::descriptions());

        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
    }
}
