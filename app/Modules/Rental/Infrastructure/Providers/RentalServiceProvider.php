<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\Rental\Application\Contracts\ActivateRentalBookingServiceInterface;
use Modules\Rental\Application\Contracts\AssignDriverServiceInterface;
use Modules\Rental\Application\Contracts\CancelRentalBookingServiceInterface;
use Modules\Rental\Application\Contracts\CompleteRentalBookingServiceInterface;
use Modules\Rental\Application\Contracts\CreateRentalBookingServiceInterface;
use Modules\Rental\Application\Contracts\CreateRentalIncidentServiceInterface;
use Modules\Rental\Application\Contracts\HoldRentalDepositServiceInterface;
use Modules\Rental\Application\Contracts\ReleaseRentalDepositServiceInterface;
use Modules\Rental\Application\Contracts\SubstituteDriverServiceInterface;
use Modules\Rental\Application\Contracts\UpdateRentalBookingServiceInterface;
use Modules\Rental\Application\Contracts\UpdateRentalIncidentServiceInterface;
use Modules\Rental\Application\Services\ActivateRentalBookingService;
use Modules\Rental\Application\Services\AssignDriverService;
use Modules\Rental\Application\Services\CancelRentalBookingService;
use Modules\Rental\Application\Services\CompleteRentalBookingService;
use Modules\Rental\Application\Services\CreateRentalBookingService;
use Modules\Rental\Application\Services\CreateRentalIncidentService;
use Modules\Rental\Application\Services\HoldRentalDepositService;
use Modules\Rental\Application\Services\ReleaseRentalDepositService;
use Modules\Rental\Application\Services\SubstituteDriverService;
use Modules\Rental\Application\Services\UpdateRentalBookingService;
use Modules\Rental\Application\Services\UpdateRentalIncidentService;
use Modules\Rental\Domain\RepositoryInterfaces\RentalBookingRepositoryInterface;
use Modules\Rental\Domain\RepositoryInterfaces\RentalDepositRepositoryInterface;
use Modules\Rental\Domain\RepositoryInterfaces\RentalDriverAssignmentRepositoryInterface;
use Modules\Rental\Domain\RepositoryInterfaces\RentalIncidentRepositoryInterface;
use Modules\Rental\Infrastructure\Persistence\Eloquent\Repositories\EloquentRentalBookingRepository;
use Modules\Rental\Infrastructure\Persistence\Eloquent\Repositories\EloquentRentalDepositRepository;
use Modules\Rental\Infrastructure\Persistence\Eloquent\Repositories\EloquentRentalDriverAssignmentRepository;
use Modules\Rental\Infrastructure\Persistence\Eloquent\Repositories\EloquentRentalIncidentRepository;

class RentalServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        // Bookings
        $this->app->bind(RentalBookingRepositoryInterface::class, EloquentRentalBookingRepository::class);
        $this->app->bind(CreateRentalBookingServiceInterface::class, CreateRentalBookingService::class);
        $this->app->bind(UpdateRentalBookingServiceInterface::class, UpdateRentalBookingService::class);
        $this->app->bind(ActivateRentalBookingServiceInterface::class, ActivateRentalBookingService::class);
        $this->app->bind(CompleteRentalBookingServiceInterface::class, CompleteRentalBookingService::class);
        $this->app->bind(CancelRentalBookingServiceInterface::class, CancelRentalBookingService::class);

        // Driver Assignments
        $this->app->bind(RentalDriverAssignmentRepositoryInterface::class, EloquentRentalDriverAssignmentRepository::class);
        $this->app->bind(AssignDriverServiceInterface::class, AssignDriverService::class);
        $this->app->bind(SubstituteDriverServiceInterface::class, SubstituteDriverService::class);

        // Incidents
        $this->app->bind(RentalIncidentRepositoryInterface::class, EloquentRentalIncidentRepository::class);
        $this->app->bind(CreateRentalIncidentServiceInterface::class, CreateRentalIncidentService::class);
        $this->app->bind(UpdateRentalIncidentServiceInterface::class, UpdateRentalIncidentService::class);

        // Deposits
        $this->app->bind(RentalDepositRepositoryInterface::class, EloquentRentalDepositRepository::class);
        $this->app->bind(HoldRentalDepositServiceInterface::class, HoldRentalDepositService::class);
        $this->app->bind(ReleaseRentalDepositServiceInterface::class, ReleaseRentalDepositService::class);
    }

    public function boot(): void
    {
        $this->bootModule(
            __DIR__.'/../../routes/api.php',
            __DIR__.'/../../database/migrations',
        );
    }
}
