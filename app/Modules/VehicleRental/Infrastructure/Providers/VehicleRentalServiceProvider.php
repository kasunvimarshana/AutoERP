<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeAgreementCreditNotes\CreateVehicleRentalLesseeAgreementCreditNoteServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeAgreementCreditNotes\DeleteVehicleRentalLesseeAgreementCreditNoteServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeAgreementCreditNotes\GetVehicleRentalLesseeAgreementCreditNoteServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeAgreementCreditNotes\ListVehicleRentalLesseeAgreementCreditNotesServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeAgreementCreditNotes\UpdateVehicleRentalLesseeAgreementCreditNoteServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeAgreementDebitNotes\CreateVehicleRentalLesseeAgreementDebitNoteServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeAgreementDebitNotes\DeleteVehicleRentalLesseeAgreementDebitNoteServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeAgreementDebitNotes\GetVehicleRentalLesseeAgreementDebitNoteServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeAgreementDebitNotes\ListVehicleRentalLesseeAgreementDebitNotesServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeAgreementDebitNotes\UpdateVehicleRentalLesseeAgreementDebitNoteServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeAgreements\CreateVehicleRentalLesseeAgreementServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeAgreements\DeleteVehicleRentalLesseeAgreementServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeAgreements\GetVehicleRentalLesseeAgreementServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeAgreements\ListVehicleRentalLesseeAgreementsServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeAgreements\UpdateVehicleRentalLesseeAgreementServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeRunningCharts\CreateVehicleRentalLesseeRunningChartServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeRunningCharts\DeleteVehicleRentalLesseeRunningChartServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeRunningCharts\GetVehicleRentalLesseeRunningChartServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeRunningCharts\ListVehicleRentalLesseeRunningChartsServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeRunningCharts\UpdateVehicleRentalLesseeRunningChartServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorAgreementCreditNotes\CreateVehicleRentalLessorAgreementCreditNoteServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorAgreementCreditNotes\DeleteVehicleRentalLessorAgreementCreditNoteServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorAgreementCreditNotes\GetVehicleRentalLessorAgreementCreditNoteServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorAgreementCreditNotes\ListVehicleRentalLessorAgreementCreditNotesServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorAgreementCreditNotes\UpdateVehicleRentalLessorAgreementCreditNoteServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorAgreementDebitNotes\CreateVehicleRentalLessorAgreementDebitNoteServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorAgreementDebitNotes\DeleteVehicleRentalLessorAgreementDebitNoteServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorAgreementDebitNotes\GetVehicleRentalLessorAgreementDebitNoteServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorAgreementDebitNotes\ListVehicleRentalLessorAgreementDebitNotesServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorAgreementDebitNotes\UpdateVehicleRentalLessorAgreementDebitNoteServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorAgreements\CreateVehicleRentalLessorAgreementServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorAgreements\DeleteVehicleRentalLessorAgreementServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorAgreements\GetVehicleRentalLessorAgreementServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorAgreements\ListVehicleRentalLessorAgreementsServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorAgreements\UpdateVehicleRentalLessorAgreementServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorRunningCharts\CreateVehicleRentalLessorRunningChartServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorRunningCharts\DeleteVehicleRentalLessorRunningChartServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorRunningCharts\GetVehicleRentalLessorRunningChartServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorRunningCharts\ListVehicleRentalLessorRunningChartsServiceInterface;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorRunningCharts\UpdateVehicleRentalLessorRunningChartServiceInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLesseeAgreementCreditNoteRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLesseeAgreementDebitNoteRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLesseeAgreementRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLesseeRunningChartRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLessorAgreementCreditNoteRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLessorAgreementDebitNoteRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLessorAgreementRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLessorRunningChartRepositoryInterface;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLesseeAgreementCreditNotes\CreateVehicleRentalLesseeAgreementCreditNoteService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLesseeAgreementCreditNotes\DeleteVehicleRentalLesseeAgreementCreditNoteService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLesseeAgreementCreditNotes\GetVehicleRentalLesseeAgreementCreditNoteService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLesseeAgreementCreditNotes\ListVehicleRentalLesseeAgreementCreditNotesService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLesseeAgreementCreditNotes\UpdateVehicleRentalLesseeAgreementCreditNoteService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLesseeAgreementDebitNotes\CreateVehicleRentalLesseeAgreementDebitNoteService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLesseeAgreementDebitNotes\DeleteVehicleRentalLesseeAgreementDebitNoteService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLesseeAgreementDebitNotes\GetVehicleRentalLesseeAgreementDebitNoteService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLesseeAgreementDebitNotes\ListVehicleRentalLesseeAgreementDebitNotesService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLesseeAgreementDebitNotes\UpdateVehicleRentalLesseeAgreementDebitNoteService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLesseeAgreements\CreateVehicleRentalLesseeAgreementService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLesseeAgreements\DeleteVehicleRentalLesseeAgreementService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLesseeAgreements\GetVehicleRentalLesseeAgreementService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLesseeAgreements\ListVehicleRentalLesseeAgreementsService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLesseeAgreements\UpdateVehicleRentalLesseeAgreementService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLesseeRunningCharts\CreateVehicleRentalLesseeRunningChartService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLesseeRunningCharts\DeleteVehicleRentalLesseeRunningChartService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLesseeRunningCharts\GetVehicleRentalLesseeRunningChartService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLesseeRunningCharts\ListVehicleRentalLesseeRunningChartsService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLesseeRunningCharts\UpdateVehicleRentalLesseeRunningChartService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLessorAgreementCreditNotes\CreateVehicleRentalLessorAgreementCreditNoteService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLessorAgreementCreditNotes\DeleteVehicleRentalLessorAgreementCreditNoteService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLessorAgreementCreditNotes\GetVehicleRentalLessorAgreementCreditNoteService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLessorAgreementCreditNotes\ListVehicleRentalLessorAgreementCreditNotesService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLessorAgreementCreditNotes\UpdateVehicleRentalLessorAgreementCreditNoteService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLessorAgreementDebitNotes\CreateVehicleRentalLessorAgreementDebitNoteService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLessorAgreementDebitNotes\DeleteVehicleRentalLessorAgreementDebitNoteService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLessorAgreementDebitNotes\GetVehicleRentalLessorAgreementDebitNoteService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLessorAgreementDebitNotes\ListVehicleRentalLessorAgreementDebitNotesService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLessorAgreementDebitNotes\UpdateVehicleRentalLessorAgreementDebitNoteService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLessorAgreements\CreateVehicleRentalLessorAgreementService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLessorAgreements\DeleteVehicleRentalLessorAgreementService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLessorAgreements\GetVehicleRentalLessorAgreementService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLessorAgreements\ListVehicleRentalLessorAgreementsService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLessorAgreements\UpdateVehicleRentalLessorAgreementService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLessorRunningCharts\CreateVehicleRentalLessorRunningChartService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLessorRunningCharts\DeleteVehicleRentalLessorRunningChartService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLessorRunningCharts\GetVehicleRentalLessorRunningChartService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLessorRunningCharts\ListVehicleRentalLessorRunningChartsService;
use Modules\VehicleRental\Application\UseCases\VehicleRentalLessorRunningCharts\UpdateVehicleRentalLessorRunningChartService;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLesseeAgreementCreditNoteModel;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLesseeAgreementDebitNoteModel;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLesseeAgreementModel;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLesseeRunningChartModel;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLessorAgreementCreditNoteModel;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLessorAgreementDebitNoteModel;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLessorAgreementModel;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLessorRunningChartModel;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleRentalLesseeAgreementCreditNoteRepository;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleRentalLesseeAgreementDebitNoteRepository;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleRentalLesseeAgreementRepository;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleRentalLesseeRunningChartRepository;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleRentalLessorAgreementCreditNoteRepository;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleRentalLessorAgreementDebitNoteRepository;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleRentalLessorAgreementRepository;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleRentalLessorRunningChartRepository;

final class VehicleRentalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/vehicle_rental.php', 'vehicle_rental');

        foreach (
            [
                ListVehicleRentalLessorAgreementsServiceInterface::class => ListVehicleRentalLessorAgreementsService::class,
                GetVehicleRentalLessorAgreementServiceInterface::class => GetVehicleRentalLessorAgreementService::class,
                CreateVehicleRentalLessorAgreementServiceInterface::class => CreateVehicleRentalLessorAgreementService::class,
                UpdateVehicleRentalLessorAgreementServiceInterface::class => UpdateVehicleRentalLessorAgreementService::class,
                DeleteVehicleRentalLessorAgreementServiceInterface::class => DeleteVehicleRentalLessorAgreementService::class,
                ListVehicleRentalLesseeAgreementsServiceInterface::class => ListVehicleRentalLesseeAgreementsService::class,
                GetVehicleRentalLesseeAgreementServiceInterface::class => GetVehicleRentalLesseeAgreementService::class,
                CreateVehicleRentalLesseeAgreementServiceInterface::class => CreateVehicleRentalLesseeAgreementService::class,
                UpdateVehicleRentalLesseeAgreementServiceInterface::class => UpdateVehicleRentalLesseeAgreementService::class,
                DeleteVehicleRentalLesseeAgreementServiceInterface::class => DeleteVehicleRentalLesseeAgreementService::class,
                ListVehicleRentalLessorRunningChartsServiceInterface::class => ListVehicleRentalLessorRunningChartsService::class,
                GetVehicleRentalLessorRunningChartServiceInterface::class => GetVehicleRentalLessorRunningChartService::class,
                CreateVehicleRentalLessorRunningChartServiceInterface::class => CreateVehicleRentalLessorRunningChartService::class,
                UpdateVehicleRentalLessorRunningChartServiceInterface::class => UpdateVehicleRentalLessorRunningChartService::class,
                DeleteVehicleRentalLessorRunningChartServiceInterface::class => DeleteVehicleRentalLessorRunningChartService::class,
                ListVehicleRentalLesseeRunningChartsServiceInterface::class => ListVehicleRentalLesseeRunningChartsService::class,
                GetVehicleRentalLesseeRunningChartServiceInterface::class => GetVehicleRentalLesseeRunningChartService::class,
                CreateVehicleRentalLesseeRunningChartServiceInterface::class => CreateVehicleRentalLesseeRunningChartService::class,
                UpdateVehicleRentalLesseeRunningChartServiceInterface::class => UpdateVehicleRentalLesseeRunningChartService::class,
                DeleteVehicleRentalLesseeRunningChartServiceInterface::class => DeleteVehicleRentalLesseeRunningChartService::class,
                ListVehicleRentalLessorAgreementCreditNotesServiceInterface::class => ListVehicleRentalLessorAgreementCreditNotesService::class,
                GetVehicleRentalLessorAgreementCreditNoteServiceInterface::class => GetVehicleRentalLessorAgreementCreditNoteService::class,
                CreateVehicleRentalLessorAgreementCreditNoteServiceInterface::class => CreateVehicleRentalLessorAgreementCreditNoteService::class,
                UpdateVehicleRentalLessorAgreementCreditNoteServiceInterface::class => UpdateVehicleRentalLessorAgreementCreditNoteService::class,
                DeleteVehicleRentalLessorAgreementCreditNoteServiceInterface::class => DeleteVehicleRentalLessorAgreementCreditNoteService::class,
                ListVehicleRentalLessorAgreementDebitNotesServiceInterface::class => ListVehicleRentalLessorAgreementDebitNotesService::class,
                GetVehicleRentalLessorAgreementDebitNoteServiceInterface::class => GetVehicleRentalLessorAgreementDebitNoteService::class,
                CreateVehicleRentalLessorAgreementDebitNoteServiceInterface::class => CreateVehicleRentalLessorAgreementDebitNoteService::class,
                UpdateVehicleRentalLessorAgreementDebitNoteServiceInterface::class => UpdateVehicleRentalLessorAgreementDebitNoteService::class,
                DeleteVehicleRentalLessorAgreementDebitNoteServiceInterface::class => DeleteVehicleRentalLessorAgreementDebitNoteService::class,
                ListVehicleRentalLesseeAgreementCreditNotesServiceInterface::class => ListVehicleRentalLesseeAgreementCreditNotesService::class,
                GetVehicleRentalLesseeAgreementCreditNoteServiceInterface::class => GetVehicleRentalLesseeAgreementCreditNoteService::class,
                CreateVehicleRentalLesseeAgreementCreditNoteServiceInterface::class => CreateVehicleRentalLesseeAgreementCreditNoteService::class,
                UpdateVehicleRentalLesseeAgreementCreditNoteServiceInterface::class => UpdateVehicleRentalLesseeAgreementCreditNoteService::class,
                DeleteVehicleRentalLesseeAgreementCreditNoteServiceInterface::class => DeleteVehicleRentalLesseeAgreementCreditNoteService::class,
                ListVehicleRentalLesseeAgreementDebitNotesServiceInterface::class => ListVehicleRentalLesseeAgreementDebitNotesService::class,
                GetVehicleRentalLesseeAgreementDebitNoteServiceInterface::class => GetVehicleRentalLesseeAgreementDebitNoteService::class,
                CreateVehicleRentalLesseeAgreementDebitNoteServiceInterface::class => CreateVehicleRentalLesseeAgreementDebitNoteService::class,
                UpdateVehicleRentalLesseeAgreementDebitNoteServiceInterface::class => UpdateVehicleRentalLesseeAgreementDebitNoteService::class,
                DeleteVehicleRentalLesseeAgreementDebitNoteServiceInterface::class => DeleteVehicleRentalLesseeAgreementDebitNoteService::class,
            ] as $contract => $implementation
        ) {
            $this->app->singleton($contract, $implementation);
        }

        $this->app->singleton(VehicleRentalLessorAgreementRepositoryInterface::class, function (): VehicleRentalLessorAgreementRepositoryInterface {
            return new EloquentVehicleRentalLessorAgreementRepository(new VehicleRentalLessorAgreementModel());
        });
        $this->app->singleton(VehicleRentalLesseeAgreementRepositoryInterface::class, function (): VehicleRentalLesseeAgreementRepositoryInterface {
            return new EloquentVehicleRentalLesseeAgreementRepository(new VehicleRentalLesseeAgreementModel());
        });
        $this->app->singleton(VehicleRentalLessorRunningChartRepositoryInterface::class, function (): VehicleRentalLessorRunningChartRepositoryInterface {
            return new EloquentVehicleRentalLessorRunningChartRepository(new VehicleRentalLessorRunningChartModel());
        });
        $this->app->singleton(VehicleRentalLesseeRunningChartRepositoryInterface::class, function (): VehicleRentalLesseeRunningChartRepositoryInterface {
            return new EloquentVehicleRentalLesseeRunningChartRepository(new VehicleRentalLesseeRunningChartModel());
        });
        $this->app->singleton(VehicleRentalLessorAgreementCreditNoteRepositoryInterface::class, function (): VehicleRentalLessorAgreementCreditNoteRepositoryInterface {
            return new EloquentVehicleRentalLessorAgreementCreditNoteRepository(new VehicleRentalLessorAgreementCreditNoteModel());
        });
        $this->app->singleton(VehicleRentalLessorAgreementDebitNoteRepositoryInterface::class, function (): VehicleRentalLessorAgreementDebitNoteRepositoryInterface {
            return new EloquentVehicleRentalLessorAgreementDebitNoteRepository(new VehicleRentalLessorAgreementDebitNoteModel());
        });
        $this->app->singleton(VehicleRentalLesseeAgreementCreditNoteRepositoryInterface::class, function (): VehicleRentalLesseeAgreementCreditNoteRepositoryInterface {
            return new EloquentVehicleRentalLesseeAgreementCreditNoteRepository(new VehicleRentalLesseeAgreementCreditNoteModel());
        });
        $this->app->singleton(VehicleRentalLesseeAgreementDebitNoteRepositoryInterface::class, function (): VehicleRentalLesseeAgreementDebitNoteRepositoryInterface {
            return new EloquentVehicleRentalLesseeAgreementDebitNoteRepository(new VehicleRentalLesseeAgreementDebitNoteModel());
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../Persistence/Eloquent/Migrations');
    }
}