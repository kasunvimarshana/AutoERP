<?php

declare(strict_types=1);

namespace Modules\Reporting\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Reporting\Domain\Contracts\ReportingRepositoryContract;
use Modules\Reporting\Infrastructure\Repositories\ReportingRepository;

class ReportingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ReportingRepositoryContract::class, ReportingRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
