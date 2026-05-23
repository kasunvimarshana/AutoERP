<?php

namespace Modules\VehicleRental\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLesseeAgreementCreditNoteRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLesseeAgreementDebitNoteRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLesseeAgreementRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLesseeRunningChartRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLessorAgreementCreditNoteRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLessorAgreementDebitNoteRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLessorAgreementRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLessorRunningChartRepositoryInterface;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleRentalLesseeAgreementCreditNoteRepository;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleRentalLesseeAgreementDebitNoteRepository;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleRentalLesseeAgreementRepository;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleRentalLesseeRunningChartRepository;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleRentalLessorAgreementCreditNoteRepository;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleRentalLessorAgreementDebitNoteRepository;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleRentalLessorAgreementRepository;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleRentalLessorRunningChartRepository;

class VehicleRentalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach ([
            VehicleRentalLesseeAgreementCreditNoteRepositoryInterface::class => EloquentVehicleRentalLesseeAgreementCreditNoteRepository::class,
            VehicleRentalLesseeAgreementDebitNoteRepositoryInterface::class => EloquentVehicleRentalLesseeAgreementDebitNoteRepository::class,
            VehicleRentalLesseeAgreementRepositoryInterface::class => EloquentVehicleRentalLesseeAgreementRepository::class,
            VehicleRentalLesseeRunningChartRepositoryInterface::class => EloquentVehicleRentalLesseeRunningChartRepository::class,
            VehicleRentalLessorAgreementCreditNoteRepositoryInterface::class => EloquentVehicleRentalLessorAgreementCreditNoteRepository::class,
            VehicleRentalLessorAgreementDebitNoteRepositoryInterface::class => EloquentVehicleRentalLessorAgreementDebitNoteRepository::class,
            VehicleRentalLessorAgreementRepositoryInterface::class => EloquentVehicleRentalLessorAgreementRepository::class,
            VehicleRentalLessorRunningChartRepositoryInterface::class => EloquentVehicleRentalLessorRunningChartRepository::class,
        ] as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../Infrastructure/Persistence/Eloquent/Migrations');
    }
}
