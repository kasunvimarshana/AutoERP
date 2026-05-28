<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\VehicleRental\Application\Contracts\Services as ServiceContracts;
use Modules\VehicleRental\Application\Repositories as Repositories;
use Modules\VehicleRental\Application\Services as Services;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models as Models;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories as EloquentRepositories;

final class VehicleRentalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/vehicle_rental.php', 'vehicle_rental');

        $bindRepository = function (string $interface, string $repository, string $model): void {
            $this->app->singleton($interface, function () use ($repository, $model) {
                return new $repository(new $model());
            });
        };

        $this->app->singleton(
            ServiceContracts\VehicleRentalManagementServiceInterface::class,
            Services\VehicleRentalManagementService::class,
        );
        $this->app->singleton(
            ServiceContracts\VehicleRentalWorkflowServiceInterface::class,
            Services\VehicleRentalWorkflowService::class,
        );
        $this->app->singleton(
            ServiceContracts\VehicleRentalIntegrationServiceInterface::class,
            Services\VehicleRentalIntegrationService::class,
        );

        $bindRepository(
            Repositories\VehicleRentalSettingRepositoryInterface::class,
            EloquentRepositories\EloquentVehicleRentalSettingRepository::class,
            Models\VehicleRentalSettingModel::class,
        );
        $bindRepository(
            Repositories\VehicleRentalVehicleRepositoryInterface::class,
            EloquentRepositories\EloquentVehicleRentalVehicleRepository::class,
            Models\VehicleRentalVehicleModel::class,
        );
        $bindRepository(
            Repositories\VehicleRentalAgreementRepositoryInterface::class,
            EloquentRepositories\EloquentVehicleRentalAgreementRepository::class,
            Models\VehicleRentalAgreementModel::class,
        );
        $bindRepository(
            Repositories\VehicleRentalAgreementLineRepositoryInterface::class,
            EloquentRepositories\EloquentVehicleRentalAgreementLineRepository::class,
            Models\VehicleRentalAgreementLineModel::class,
        );
        $bindRepository(
            Repositories\VehicleRentalAgreementRateRepositoryInterface::class,
            EloquentRepositories\EloquentVehicleRentalAgreementRateRepository::class,
            Models\VehicleRentalAgreementRateModel::class,
        );
        $bindRepository(
            Repositories\VehicleRentalRateRuleRepositoryInterface::class,
            EloquentRepositories\EloquentVehicleRentalRateRuleRepository::class,
            Models\VehicleRentalRateRuleModel::class,
        );
        $bindRepository(
            Repositories\VehicleRentalRunningChartRepositoryInterface::class,
            EloquentRepositories\EloquentVehicleRentalRunningChartRepository::class,
            Models\VehicleRentalRunningChartModel::class,
        );
        $bindRepository(
            Repositories\VehicleRentalRunningChartLineRepositoryInterface::class,
            EloquentRepositories\EloquentVehicleRentalRunningChartLineRepository::class,
            Models\VehicleRentalRunningChartLineModel::class,
        );
        $bindRepository(
            Repositories\VehicleRentalExtraChargeRepositoryInterface::class,
            EloquentRepositories\EloquentVehicleRentalExtraChargeRepository::class,
            Models\VehicleRentalExtraChargeModel::class,
        );
        $bindRepository(
            Repositories\VehicleRentalReplacementRepositoryInterface::class,
            EloquentRepositories\EloquentVehicleRentalReplacementRepository::class,
            Models\VehicleRentalReplacementModel::class,
        );
        $bindRepository(
            Repositories\VehicleRentalBreakdownRepositoryInterface::class,
            EloquentRepositories\EloquentVehicleRentalBreakdownRepository::class,
            Models\VehicleRentalBreakdownModel::class,
        );
        $bindRepository(
            Repositories\VehicleRentalDocumentLinkRepositoryInterface::class,
            EloquentRepositories\EloquentVehicleRentalDocumentLinkRepository::class,
            Models\VehicleRentalDocumentLinkModel::class,
        );
        $bindRepository(
            Repositories\VehicleRentalPaymentLinkRepositoryInterface::class,
            EloquentRepositories\EloquentVehicleRentalPaymentLinkRepository::class,
            Models\VehicleRentalPaymentLinkModel::class,
        );
        $bindRepository(
            Repositories\VehicleRentalProviderPayableRepositoryInterface::class,
            EloquentRepositories\EloquentVehicleRentalProviderPayableRepository::class,
            Models\VehicleRentalProviderPayableModel::class,
        );
        $bindRepository(
            Repositories\VehicleRentalStatusHistoryRepositoryInterface::class,
            EloquentRepositories\EloquentVehicleRentalStatusHistoryRepository::class,
            Models\VehicleRentalStatusHistoryModel::class,
        );
        $bindRepository(
            Repositories\VehicleRentalApprovalHistoryRepositoryInterface::class,
            EloquentRepositories\EloquentVehicleRentalApprovalHistoryRepository::class,
            Models\VehicleRentalApprovalHistoryModel::class,
        );
        $bindRepository(
            Repositories\VehicleRentalMetadataDefinitionRepositoryInterface::class,
            EloquentRepositories\EloquentVehicleRentalMetadataDefinitionRepository::class,
            Models\VehicleRentalMetadataDefinitionModel::class,
        );
        $bindRepository(
            Repositories\VehicleRentalMetadataValueRepositoryInterface::class,
            EloquentRepositories\EloquentVehicleRentalMetadataValueRepository::class,
            Models\VehicleRentalMetadataValueModel::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../Persistence/Eloquent/Migrations');
    }
}
