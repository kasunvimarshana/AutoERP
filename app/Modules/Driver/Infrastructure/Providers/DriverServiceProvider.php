<?php declare(strict_types=1);

namespace Modules\Driver\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\Driver\Domain\RepositoryInterfaces\DriverRepositoryInterface;
use Modules\Driver\Domain\RepositoryInterfaces\LicenseRepositoryInterface;
use Modules\Driver\Domain\RepositoryInterfaces\DriverAvailabilityRepositoryInterface;
use Modules\Driver\Domain\RepositoryInterfaces\DriverCommissionRepositoryInterface;
use Modules\Driver\Infrastructure\Persistence\Eloquent\Repositories\EloquentDriverRepository;
use Modules\Driver\Infrastructure\Persistence\Eloquent\Repositories\EloquentLicenseRepository;
use Modules\Driver\Infrastructure\Persistence\Eloquent\Repositories\EloquentDriverAvailabilityRepository;
use Modules\Driver\Infrastructure\Persistence\Eloquent\Repositories\EloquentDriverCommissionRepository;
use Modules\Driver\Application\Contracts\ManageDriverServiceInterface;
use Modules\Driver\Application\Contracts\ManageLicenseServiceInterface;
use Modules\Driver\Application\Contracts\ManageAvailabilityServiceInterface;
use Modules\Driver\Application\Contracts\ManageCommissionServiceInterface;
use Modules\Driver\Application\Services\ManageDriverService;
use Modules\Driver\Application\Services\ManageLicenseService;
use Modules\Driver\Application\Services\ManageAvailabilityService;
use Modules\Driver\Application\Services\ManageCommissionService;

class DriverServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        // Repository bindings
        $this->app->bind(
            DriverRepositoryInterface::class,
            EloquentDriverRepository::class
        );

        $this->app->bind(
            LicenseRepositoryInterface::class,
            EloquentLicenseRepository::class
        );

        $this->app->bind(
            DriverAvailabilityRepositoryInterface::class,
            EloquentDriverAvailabilityRepository::class
        );

        $this->app->bind(
            DriverCommissionRepositoryInterface::class,
            EloquentDriverCommissionRepository::class
        );

        // Service bindings
        $this->app->bind(ManageDriverServiceInterface::class, ManageDriverService::class);
        $this->app->bind(ManageLicenseServiceInterface::class, ManageLicenseService::class);
        $this->app->bind(ManageAvailabilityServiceInterface::class, ManageAvailabilityService::class);
        $this->app->bind(ManageCommissionServiceInterface::class, ManageCommissionService::class);
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__.'/../../routes/api.php',
            __DIR__.'/../../database/migrations',
            '',
            []
        );
    }
}
