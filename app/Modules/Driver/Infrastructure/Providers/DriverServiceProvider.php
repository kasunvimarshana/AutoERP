<?php

declare(strict_types=1);

namespace Modules\Driver\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\Driver\Application\Contracts\DriverLicenseServiceInterface;
use Modules\Driver\Application\Contracts\DriverServiceInterface;
use Modules\Driver\Application\Services\DriverLicenseService;
use Modules\Driver\Application\Services\DriverService;
use Modules\Driver\Domain\RepositoryInterfaces\DriverLicenseRepositoryInterface;
use Modules\Driver\Domain\RepositoryInterfaces\DriverRepositoryInterface;
use Modules\Driver\Infrastructure\Persistence\Eloquent\Repositories\EloquentDriverLicenseRepository;
use Modules\Driver\Infrastructure\Persistence\Eloquent\Repositories\EloquentDriverRepository;

class DriverServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        $this->app->bind(DriverRepositoryInterface::class, EloquentDriverRepository::class);
        $this->app->bind(DriverLicenseRepositoryInterface::class, EloquentDriverLicenseRepository::class);

        $this->app->bind(DriverServiceInterface::class, DriverService::class);
        $this->app->bind(DriverLicenseServiceInterface::class, DriverLicenseService::class);
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__ . '/../../routes/api.php',
            __DIR__ . '/../../database/migrations',
        );
    }
}
