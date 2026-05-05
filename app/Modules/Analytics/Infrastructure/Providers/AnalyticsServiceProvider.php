<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Analytics\Application\Contracts\AnalyticsServiceInterface;
use Modules\Analytics\Application\Services\AnalyticsService;
use Modules\Analytics\Domain\RepositoryInterfaces\AnalyticsSnapshotRepositoryInterface;
use Modules\Analytics\Infrastructure\Persistence\Eloquent\Repositories\EloquentAnalyticsSnapshotRepository;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;

class AnalyticsServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $this->app->bind(AnalyticsSnapshotRepositoryInterface::class, EloquentAnalyticsSnapshotRepository::class);
        $this->app->bind(AnalyticsServiceInterface::class, AnalyticsService::class);
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__ . '/../../routes/api.php',
            __DIR__ . '/../../database/migrations',
        );
    }
}
