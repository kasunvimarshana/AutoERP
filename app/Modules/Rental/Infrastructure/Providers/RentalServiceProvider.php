<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Infrastructure\Concerns\LoadsModuleRoutesAndMigrations;
use Modules\Rental\Application\Contracts\CalculateRentalChargeServiceInterface;
use Modules\Rental\Application\Contracts\CheckInVehicleServiceInterface;
use Modules\Rental\Application\Contracts\CheckOutVehicleServiceInterface;
use Modules\Rental\Application\Contracts\CreateRentalAgreementServiceInterface;
use Modules\Rental\Application\Contracts\CreateRentalReservationServiceInterface;
use Modules\Rental\Application\Contracts\ManageRentalReservationServiceInterface;
use Modules\Rental\Application\Contracts\ManageRentalAgreementServiceInterface;
use Modules\Rental\Application\Contracts\ManageRentalTransactionServiceInterface;
use Modules\Rental\Application\Services\CalculateRentalChargeService;
use Modules\Rental\Application\Services\CheckInVehicleService;
use Modules\Rental\Application\Services\CheckOutVehicleService;
use Modules\Rental\Application\Services\CreateRentalAgreementService;
use Modules\Rental\Application\Services\CreateRentalReservationService;
use Modules\Rental\Application\Services\ManageRentalReservationService;
use Modules\Rental\Application\Services\ManageRentalAgreementService;
use Modules\Rental\Application\Services\ManageRentalTransactionService;
use Modules\Rental\Domain\RepositoryInterfaces\RentalAgreementRepositoryInterface;
use Modules\Rental\Domain\RepositoryInterfaces\RentalReservationRepositoryInterface;
use Modules\Rental\Domain\RepositoryInterfaces\RentalTransactionRepositoryInterface;
use Modules\Rental\Infrastructure\Persistence\Eloquent\Repositories\EloquentRentalAgreementRepository;
use Modules\Rental\Infrastructure\Persistence\Eloquent\Repositories\EloquentRentalReservationRepository;
use Modules\Rental\Infrastructure\Persistence\Eloquent\Repositories\EloquentRentalTransactionRepository;

class RentalServiceProvider extends ServiceProvider
{
    use LoadsModuleRoutesAndMigrations;

    public function register(): void
    {
        // Repository bindings
        $this->app->bind(RentalReservationRepositoryInterface::class, EloquentRentalReservationRepository::class);
        $this->app->bind(RentalAgreementRepositoryInterface::class, EloquentRentalAgreementRepository::class);
        $this->app->bind(RentalTransactionRepositoryInterface::class, EloquentRentalTransactionRepository::class);

        // Domain service bindings (DTO-based)
        $this->app->bind(CreateRentalReservationServiceInterface::class, CreateRentalReservationService::class);
        $this->app->bind(CreateRentalAgreementServiceInterface::class, CreateRentalAgreementService::class);
        $this->app->bind(CheckOutVehicleServiceInterface::class, CheckOutVehicleService::class);
        $this->app->bind(CheckInVehicleServiceInterface::class, CheckInVehicleService::class);
        $this->app->bind(CalculateRentalChargeServiceInterface::class, CalculateRentalChargeService::class);

        // Management service bindings (for controllers)
        $this->app->bind(ManageRentalReservationServiceInterface::class, ManageRentalReservationService::class);
        $this->app->bind(ManageRentalAgreementServiceInterface::class, ManageRentalAgreementService::class);
        $this->app->bind(ManageRentalTransactionServiceInterface::class, ManageRentalTransactionService::class);
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
