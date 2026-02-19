<?php

declare(strict_types=1);

namespace Modules\Reporting\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Reporting\Models\Dashboard;
use Modules\Reporting\Models\DashboardWidget;
use Modules\Reporting\Models\Report;
use Modules\Reporting\Policies\DashboardPolicy;
use Modules\Reporting\Policies\ReportPolicy;
use Modules\Reporting\Policies\WidgetPolicy;

class ReportingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register module configuration
        $this->mergeConfigFrom(__DIR__.'/../Config/reporting.php', 'reporting');

        // Register repositories
        $this->app->singleton(\Modules\Reporting\Repositories\ReportRepository::class);
        $this->app->singleton(\Modules\Reporting\Repositories\SavedReportRepository::class);
        $this->app->singleton(\Modules\Reporting\Repositories\DashboardRepository::class);
        $this->app->singleton(\Modules\Reporting\Repositories\WidgetRepository::class);
        $this->app->singleton(\Modules\Reporting\Repositories\ScheduleRepository::class);
        $this->app->singleton(\Modules\Reporting\Repositories\ExecutionRepository::class);

        // Register services
        $this->app->singleton(\Modules\Reporting\Services\ReportBuilderService::class);
        $this->app->singleton(\Modules\Reporting\Services\ReportExportService::class);
        $this->app->singleton(\Modules\Reporting\Services\DashboardService::class);
        $this->app->singleton(\Modules\Reporting\Services\AnalyticsService::class);
        $this->app->singleton(\Modules\Reporting\Services\ScheduledReportService::class);
    }

    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        // Register policies
        Gate::policy(Report::class, ReportPolicy::class);
        Gate::policy(Dashboard::class, DashboardPolicy::class);
        Gate::policy(DashboardWidget::class, WidgetPolicy::class);

        // Register scheduled tasks
        if (config('reporting.scheduling.enabled', true)) {
            $this->registerScheduledTasks();
        }
    }

    /**
     * Register scheduled tasks for report execution
     */
    private function registerScheduledTasks(): void
    {
        if ($this->app->runningInConsole()) {
            $this->app->booted(function () {
                $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);

                // Execute scheduled reports every 5 minutes
                $schedule->call(function () {
                    $service = app(\Modules\Reporting\Services\ScheduledReportService::class);
                    $service->executeScheduled();
                })->everyFiveMinutes()->name('reporting:execute-scheduled');

                // Cleanup old exports daily
                $schedule->call(function () {
                    $service = app(\Modules\Reporting\Services\ReportExportService::class);
                    $service->cleanupOldExports(config('reporting.exports.cleanup_days', 7));
                })->daily()->name('reporting:cleanup-exports');
            });
        }
    }
}
