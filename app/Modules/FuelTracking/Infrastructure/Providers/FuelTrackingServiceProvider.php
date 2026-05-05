<?php

declare(strict_types=1);

namespace Modules\FuelTracking\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\FuelTracking\Application\Contracts\FuelLogServiceInterface;
use Modules\FuelTracking\Application\Services\FuelLogService;
use Modules\FuelTracking\Domain\RepositoryInterfaces\FuelLogRepositoryInterface;
use Modules\FuelTracking\Infrastructure\Persistence\Eloquent\Repositories\EloquentFuelLogRepository;

class FuelTrackingServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $this->app->bind(FuelLogRepositoryInterface::class, EloquentFuelLogRepository::class);
        $this->app->bind(FuelLogServiceInterface::class, FuelLogService::class);
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__ . '/../../routes/api.php',
            __DIR__ . '/../../database/migrations',
        );
    }
}
