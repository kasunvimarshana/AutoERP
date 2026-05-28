<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceDiagnosticLines\CreateVehicleServiceDiagnosticLineServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceDiagnosticLines\DeleteVehicleServiceDiagnosticLineServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceDiagnosticLines\GetVehicleServiceDiagnosticLineServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceDiagnosticLines\ListVehicleServiceDiagnosticLinesServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceDiagnosticLines\UpdateVehicleServiceDiagnosticLineServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceDiagnostics\CreateVehicleServiceDiagnosticServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceDiagnostics\DeleteVehicleServiceDiagnosticServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceDiagnostics\GetVehicleServiceDiagnosticServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceDiagnostics\ListVehicleServiceDiagnosticsServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceDiagnostics\UpdateVehicleServiceDiagnosticServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceInspectionLines\CreateVehicleServiceInspectionLineServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceInspectionLines\DeleteVehicleServiceInspectionLineServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceInspectionLines\GetVehicleServiceInspectionLineServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceInspectionLines\ListVehicleServiceInspectionLinesServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceInspectionLines\UpdateVehicleServiceInspectionLineServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceInspections\CreateVehicleServiceInspectionServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceInspections\DeleteVehicleServiceInspectionServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceInspections\GetVehicleServiceInspectionServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceInspections\ListVehicleServiceInspectionsServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceInspections\UpdateVehicleServiceInspectionServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceJobCardLines\CreateVehicleServiceJobCardLineServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceJobCardLines\DeleteVehicleServiceJobCardLineServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceJobCardLines\GetVehicleServiceJobCardLineServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceJobCardLines\ListVehicleServiceJobCardLinesServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceJobCardLines\UpdateVehicleServiceJobCardLineServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceJobCards\CreateVehicleServiceJobCardServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceJobCards\DeleteVehicleServiceJobCardServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceJobCards\GetVehicleServiceJobCardServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceJobCards\ListVehicleServiceJobCardsServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceJobCards\UpdateVehicleServiceJobCardServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceLaborAssignments\CreateVehicleServiceLaborAssignmentServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceLaborAssignments\DeleteVehicleServiceLaborAssignmentServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceLaborAssignments\GetVehicleServiceLaborAssignmentServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceLaborAssignments\ListVehicleServiceLaborAssignmentsServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceLaborAssignments\UpdateVehicleServiceLaborAssignmentServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceLaborItems\CreateVehicleServiceLaborItemServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceLaborItems\DeleteVehicleServiceLaborItemServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceLaborItems\GetVehicleServiceLaborItemServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceLaborItems\ListVehicleServiceLaborItemsServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceLaborItems\UpdateVehicleServiceLaborItemServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceNonInventoryItems\CreateVehicleServiceNonInventoryItemServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceNonInventoryItems\DeleteVehicleServiceNonInventoryItemServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceNonInventoryItems\GetVehicleServiceNonInventoryItemServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceNonInventoryItems\ListVehicleServiceNonInventoryItemsServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceNonInventoryItems\UpdateVehicleServiceNonInventoryItemServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceTypes\CreateVehicleServiceTypeServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceTypes\DeleteVehicleServiceTypeServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceTypes\GetVehicleServiceTypeServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceTypes\ListVehicleServiceTypesServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceTypes\UpdateVehicleServiceTypeServiceInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceDiagnosticLineRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceDiagnosticRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceInspectionLineRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceInspectionRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobCardLineRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobCardRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceLaborAssignmentRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceLaborItemRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceNonInventoryItemRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceTypeRepositoryInterface;
use Modules\VehicleService\Application\UseCases\VehicleServiceDiagnosticLines\CreateVehicleServiceDiagnosticLineService;
use Modules\VehicleService\Application\UseCases\VehicleServiceDiagnosticLines\DeleteVehicleServiceDiagnosticLineService;
use Modules\VehicleService\Application\UseCases\VehicleServiceDiagnosticLines\GetVehicleServiceDiagnosticLineService;
use Modules\VehicleService\Application\UseCases\VehicleServiceDiagnosticLines\ListVehicleServiceDiagnosticLinesService;
use Modules\VehicleService\Application\UseCases\VehicleServiceDiagnosticLines\UpdateVehicleServiceDiagnosticLineService;
use Modules\VehicleService\Application\UseCases\VehicleServiceDiagnostics\CreateVehicleServiceDiagnosticService;
use Modules\VehicleService\Application\UseCases\VehicleServiceDiagnostics\DeleteVehicleServiceDiagnosticService;
use Modules\VehicleService\Application\UseCases\VehicleServiceDiagnostics\GetVehicleServiceDiagnosticService;
use Modules\VehicleService\Application\UseCases\VehicleServiceDiagnostics\ListVehicleServiceDiagnosticsService;
use Modules\VehicleService\Application\UseCases\VehicleServiceDiagnostics\UpdateVehicleServiceDiagnosticService;
use Modules\VehicleService\Application\UseCases\VehicleServiceInspectionLines\CreateVehicleServiceInspectionLineService;
use Modules\VehicleService\Application\UseCases\VehicleServiceInspectionLines\DeleteVehicleServiceInspectionLineService;
use Modules\VehicleService\Application\UseCases\VehicleServiceInspectionLines\GetVehicleServiceInspectionLineService;
use Modules\VehicleService\Application\UseCases\VehicleServiceInspectionLines\ListVehicleServiceInspectionLinesService;
use Modules\VehicleService\Application\UseCases\VehicleServiceInspectionLines\UpdateVehicleServiceInspectionLineService;
use Modules\VehicleService\Application\UseCases\VehicleServiceInspections\CreateVehicleServiceInspectionService;
use Modules\VehicleService\Application\UseCases\VehicleServiceInspections\DeleteVehicleServiceInspectionService;
use Modules\VehicleService\Application\UseCases\VehicleServiceInspections\GetVehicleServiceInspectionService;
use Modules\VehicleService\Application\UseCases\VehicleServiceInspections\ListVehicleServiceInspectionsService;
use Modules\VehicleService\Application\UseCases\VehicleServiceInspections\UpdateVehicleServiceInspectionService;
use Modules\VehicleService\Application\UseCases\VehicleServiceJobCardLines\CreateVehicleServiceJobCardLineService;
use Modules\VehicleService\Application\UseCases\VehicleServiceJobCardLines\DeleteVehicleServiceJobCardLineService;
use Modules\VehicleService\Application\UseCases\VehicleServiceJobCardLines\GetVehicleServiceJobCardLineService;
use Modules\VehicleService\Application\UseCases\VehicleServiceJobCardLines\ListVehicleServiceJobCardLinesService;
use Modules\VehicleService\Application\UseCases\VehicleServiceJobCardLines\UpdateVehicleServiceJobCardLineService;
use Modules\VehicleService\Application\UseCases\VehicleServiceJobCards\CreateVehicleServiceJobCardService;
use Modules\VehicleService\Application\UseCases\VehicleServiceJobCards\DeleteVehicleServiceJobCardService;
use Modules\VehicleService\Application\UseCases\VehicleServiceJobCards\GetVehicleServiceJobCardService;
use Modules\VehicleService\Application\UseCases\VehicleServiceJobCards\ListVehicleServiceJobCardsService;
use Modules\VehicleService\Application\UseCases\VehicleServiceJobCards\UpdateVehicleServiceJobCardService;
use Modules\VehicleService\Application\UseCases\VehicleServiceLaborAssignments\CreateVehicleServiceLaborAssignmentService;
use Modules\VehicleService\Application\UseCases\VehicleServiceLaborAssignments\DeleteVehicleServiceLaborAssignmentService;
use Modules\VehicleService\Application\UseCases\VehicleServiceLaborAssignments\GetVehicleServiceLaborAssignmentService;
use Modules\VehicleService\Application\UseCases\VehicleServiceLaborAssignments\ListVehicleServiceLaborAssignmentsService;
use Modules\VehicleService\Application\UseCases\VehicleServiceLaborAssignments\UpdateVehicleServiceLaborAssignmentService;
use Modules\VehicleService\Application\UseCases\VehicleServiceLaborItems\CreateVehicleServiceLaborItemService;
use Modules\VehicleService\Application\UseCases\VehicleServiceLaborItems\DeleteVehicleServiceLaborItemService;
use Modules\VehicleService\Application\UseCases\VehicleServiceLaborItems\GetVehicleServiceLaborItemService;
use Modules\VehicleService\Application\UseCases\VehicleServiceLaborItems\ListVehicleServiceLaborItemsService;
use Modules\VehicleService\Application\UseCases\VehicleServiceLaborItems\UpdateVehicleServiceLaborItemService;
use Modules\VehicleService\Application\UseCases\VehicleServiceNonInventoryItems\CreateVehicleServiceNonInventoryItemService;
use Modules\VehicleService\Application\UseCases\VehicleServiceNonInventoryItems\DeleteVehicleServiceNonInventoryItemService;
use Modules\VehicleService\Application\UseCases\VehicleServiceNonInventoryItems\GetVehicleServiceNonInventoryItemService;
use Modules\VehicleService\Application\UseCases\VehicleServiceNonInventoryItems\ListVehicleServiceNonInventoryItemsService;
use Modules\VehicleService\Application\UseCases\VehicleServiceNonInventoryItems\UpdateVehicleServiceNonInventoryItemService;
use Modules\VehicleService\Application\UseCases\VehicleServiceTypes\CreateVehicleServiceTypeService;
use Modules\VehicleService\Application\UseCases\VehicleServiceTypes\DeleteVehicleServiceTypeService;
use Modules\VehicleService\Application\UseCases\VehicleServiceTypes\GetVehicleServiceTypeService;
use Modules\VehicleService\Application\UseCases\VehicleServiceTypes\ListVehicleServiceTypesService;
use Modules\VehicleService\Application\UseCases\VehicleServiceTypes\UpdateVehicleServiceTypeService;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceDiagnosticLineModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceDiagnosticModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceInspectionLineModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceInspectionModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceJobCardLineModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceJobCardModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceLaborAssignmentModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceLaborItemModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceNonInventoryItemModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceTypeModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleServiceDiagnosticLineRepository;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleServiceDiagnosticRepository;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleServiceInspectionLineRepository;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleServiceInspectionRepository;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleServiceJobCardLineRepository;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleServiceJobCardRepository;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleServiceLaborAssignmentRepository;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleServiceLaborItemRepository;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleServiceNonInventoryItemRepository;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleServiceTypeRepository;

final class VehicleServiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/vehicle_service.php', 'vehicle_service');

        foreach (
            [
                ListVehicleServiceTypesServiceInterface::class => ListVehicleServiceTypesService::class,
                GetVehicleServiceTypeServiceInterface::class => GetVehicleServiceTypeService::class,
                CreateVehicleServiceTypeServiceInterface::class => CreateVehicleServiceTypeService::class,
                UpdateVehicleServiceTypeServiceInterface::class => UpdateVehicleServiceTypeService::class,
                DeleteVehicleServiceTypeServiceInterface::class => DeleteVehicleServiceTypeService::class,
                ListVehicleServiceJobCardsServiceInterface::class => ListVehicleServiceJobCardsService::class,
                GetVehicleServiceJobCardServiceInterface::class => GetVehicleServiceJobCardService::class,
                CreateVehicleServiceJobCardServiceInterface::class => CreateVehicleServiceJobCardService::class,
                UpdateVehicleServiceJobCardServiceInterface::class => UpdateVehicleServiceJobCardService::class,
                DeleteVehicleServiceJobCardServiceInterface::class => DeleteVehicleServiceJobCardService::class,
                ListVehicleServiceJobCardLinesServiceInterface::class => ListVehicleServiceJobCardLinesService::class,
                GetVehicleServiceJobCardLineServiceInterface::class => GetVehicleServiceJobCardLineService::class,
                CreateVehicleServiceJobCardLineServiceInterface::class => CreateVehicleServiceJobCardLineService::class,
                UpdateVehicleServiceJobCardLineServiceInterface::class => UpdateVehicleServiceJobCardLineService::class,
                DeleteVehicleServiceJobCardLineServiceInterface::class => DeleteVehicleServiceJobCardLineService::class,
                ListVehicleServiceLaborItemsServiceInterface::class => ListVehicleServiceLaborItemsService::class,
                GetVehicleServiceLaborItemServiceInterface::class => GetVehicleServiceLaborItemService::class,
                CreateVehicleServiceLaborItemServiceInterface::class => CreateVehicleServiceLaborItemService::class,
                UpdateVehicleServiceLaborItemServiceInterface::class => UpdateVehicleServiceLaborItemService::class,
                DeleteVehicleServiceLaborItemServiceInterface::class => DeleteVehicleServiceLaborItemService::class,
                ListVehicleServiceNonInventoryItemsServiceInterface::class => ListVehicleServiceNonInventoryItemsService::class,
                GetVehicleServiceNonInventoryItemServiceInterface::class => GetVehicleServiceNonInventoryItemService::class,
                CreateVehicleServiceNonInventoryItemServiceInterface::class => CreateVehicleServiceNonInventoryItemService::class,
                UpdateVehicleServiceNonInventoryItemServiceInterface::class => UpdateVehicleServiceNonInventoryItemService::class,
                DeleteVehicleServiceNonInventoryItemServiceInterface::class => DeleteVehicleServiceNonInventoryItemService::class,
                ListVehicleServiceLaborAssignmentsServiceInterface::class => ListVehicleServiceLaborAssignmentsService::class,
                GetVehicleServiceLaborAssignmentServiceInterface::class => GetVehicleServiceLaborAssignmentService::class,
                CreateVehicleServiceLaborAssignmentServiceInterface::class => CreateVehicleServiceLaborAssignmentService::class,
                UpdateVehicleServiceLaborAssignmentServiceInterface::class => UpdateVehicleServiceLaborAssignmentService::class,
                DeleteVehicleServiceLaborAssignmentServiceInterface::class => DeleteVehicleServiceLaborAssignmentService::class,
                ListVehicleServiceDiagnosticsServiceInterface::class => ListVehicleServiceDiagnosticsService::class,
                GetVehicleServiceDiagnosticServiceInterface::class => GetVehicleServiceDiagnosticService::class,
                CreateVehicleServiceDiagnosticServiceInterface::class => CreateVehicleServiceDiagnosticService::class,
                UpdateVehicleServiceDiagnosticServiceInterface::class => UpdateVehicleServiceDiagnosticService::class,
                DeleteVehicleServiceDiagnosticServiceInterface::class => DeleteVehicleServiceDiagnosticService::class,
                ListVehicleServiceDiagnosticLinesServiceInterface::class => ListVehicleServiceDiagnosticLinesService::class,
                GetVehicleServiceDiagnosticLineServiceInterface::class => GetVehicleServiceDiagnosticLineService::class,
                CreateVehicleServiceDiagnosticLineServiceInterface::class => CreateVehicleServiceDiagnosticLineService::class,
                UpdateVehicleServiceDiagnosticLineServiceInterface::class => UpdateVehicleServiceDiagnosticLineService::class,
                DeleteVehicleServiceDiagnosticLineServiceInterface::class => DeleteVehicleServiceDiagnosticLineService::class,
                ListVehicleServiceInspectionsServiceInterface::class => ListVehicleServiceInspectionsService::class,
                GetVehicleServiceInspectionServiceInterface::class => GetVehicleServiceInspectionService::class,
                CreateVehicleServiceInspectionServiceInterface::class => CreateVehicleServiceInspectionService::class,
                UpdateVehicleServiceInspectionServiceInterface::class => UpdateVehicleServiceInspectionService::class,
                DeleteVehicleServiceInspectionServiceInterface::class => DeleteVehicleServiceInspectionService::class,
                ListVehicleServiceInspectionLinesServiceInterface::class => ListVehicleServiceInspectionLinesService::class,
                GetVehicleServiceInspectionLineServiceInterface::class => GetVehicleServiceInspectionLineService::class,
                CreateVehicleServiceInspectionLineServiceInterface::class => CreateVehicleServiceInspectionLineService::class,
                UpdateVehicleServiceInspectionLineServiceInterface::class => UpdateVehicleServiceInspectionLineService::class,
                DeleteVehicleServiceInspectionLineServiceInterface::class => DeleteVehicleServiceInspectionLineService::class,
            ] as $contract => $implementation
        ) {
            $this->app->singleton($contract, $implementation);
        }

        $this->app->singleton(
            \Modules\VehicleService\Application\Contracts\Services\VehicleServiceManagementServiceInterface::class,
            \Modules\VehicleService\Application\Services\VehicleServiceManagementService::class,
        );
        $this->app->singleton(
            \Modules\VehicleService\Application\Contracts\Services\VehicleServiceWorkflowServiceInterface::class,
            \Modules\VehicleService\Application\Services\VehicleServiceWorkflowService::class,
        );
        $this->app->singleton(
            \Modules\VehicleService\Application\Contracts\Services\VehicleServiceIntegrationServiceInterface::class,
            \Modules\VehicleService\Application\Services\VehicleServiceIntegrationService::class,
        );

        $this->app->singleton(VehicleServiceTypeRepositoryInterface::class, function (): VehicleServiceTypeRepositoryInterface {
            return new EloquentVehicleServiceTypeRepository(new VehicleServiceTypeModel());
        });
        $this->app->singleton(VehicleServiceJobCardRepositoryInterface::class, function (): VehicleServiceJobCardRepositoryInterface {
            return new EloquentVehicleServiceJobCardRepository(new VehicleServiceJobCardModel());
        });
        $this->app->singleton(VehicleServiceJobCardLineRepositoryInterface::class, function (): VehicleServiceJobCardLineRepositoryInterface {
            return new EloquentVehicleServiceJobCardLineRepository(new VehicleServiceJobCardLineModel());
        });
        $this->app->singleton(VehicleServiceLaborItemRepositoryInterface::class, function (): VehicleServiceLaborItemRepositoryInterface {
            return new EloquentVehicleServiceLaborItemRepository(new VehicleServiceLaborItemModel());
        });
        $this->app->singleton(VehicleServiceNonInventoryItemRepositoryInterface::class, function (): VehicleServiceNonInventoryItemRepositoryInterface {
            return new EloquentVehicleServiceNonInventoryItemRepository(new VehicleServiceNonInventoryItemModel());
        });
        $this->app->singleton(VehicleServiceLaborAssignmentRepositoryInterface::class, function (): VehicleServiceLaborAssignmentRepositoryInterface {
            return new EloquentVehicleServiceLaborAssignmentRepository(new VehicleServiceLaborAssignmentModel());
        });
        $this->app->singleton(VehicleServiceDiagnosticRepositoryInterface::class, function (): VehicleServiceDiagnosticRepositoryInterface {
            return new EloquentVehicleServiceDiagnosticRepository(new VehicleServiceDiagnosticModel());
        });
        $this->app->singleton(VehicleServiceDiagnosticLineRepositoryInterface::class, function (): VehicleServiceDiagnosticLineRepositoryInterface {
            return new EloquentVehicleServiceDiagnosticLineRepository(new VehicleServiceDiagnosticLineModel());
        });
        $this->app->singleton(VehicleServiceInspectionRepositoryInterface::class, function (): VehicleServiceInspectionRepositoryInterface {
            return new EloquentVehicleServiceInspectionRepository(new VehicleServiceInspectionModel());
        });
        $this->app->singleton(VehicleServiceInspectionLineRepositoryInterface::class, function (): VehicleServiceInspectionLineRepositoryInterface {
            return new EloquentVehicleServiceInspectionLineRepository(new VehicleServiceInspectionLineModel());
        });
        $this->app->singleton(
            \Modules\VehicleService\Application\Repositories\VehicleServiceSettingRepositoryInterface::class,
            function (): \Modules\VehicleService\Application\Repositories\VehicleServiceSettingRepositoryInterface {
                return new \Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleServiceSettingRepository(
                    new \Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceSettingModel(),
                );
            },
        );
        $this->app->singleton(
            \Modules\VehicleService\Application\Repositories\VehicleServiceJobExternalServiceRepositoryInterface::class,
            function (): \Modules\VehicleService\Application\Repositories\VehicleServiceJobExternalServiceRepositoryInterface {
                return new \Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleServiceJobExternalServiceRepository(
                    new \Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceJobExternalServiceModel(),
                );
            },
        );
        $this->app->singleton(
            \Modules\VehicleService\Application\Repositories\VehicleServiceJobCustomerSuppliedItemRepositoryInterface::class,
            function (): \Modules\VehicleService\Application\Repositories\VehicleServiceJobCustomerSuppliedItemRepositoryInterface {
                return new \Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleServiceJobCustomerSuppliedItemRepository(
                    new \Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceJobCustomerSuppliedItemModel(),
                );
            },
        );
        $this->app->singleton(
            \Modules\VehicleService\Application\Repositories\VehicleServiceJobStatusHistoryRepositoryInterface::class,
            function (): \Modules\VehicleService\Application\Repositories\VehicleServiceJobStatusHistoryRepositoryInterface {
                return new \Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleServiceJobStatusHistoryRepository(
                    new \Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceJobStatusHistoryModel(),
                );
            },
        );
        $this->app->singleton(
            \Modules\VehicleService\Application\Repositories\VehicleServiceJobDocumentLinkRepositoryInterface::class,
            function (): \Modules\VehicleService\Application\Repositories\VehicleServiceJobDocumentLinkRepositoryInterface {
                return new \Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleServiceJobDocumentLinkRepository(
                    new \Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceJobDocumentLinkModel(),
                );
            },
        );
        $this->app->singleton(
            \Modules\VehicleService\Application\Repositories\VehicleServiceJobPaymentLinkRepositoryInterface::class,
            function (): \Modules\VehicleService\Application\Repositories\VehicleServiceJobPaymentLinkRepositoryInterface {
                return new \Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleServiceJobPaymentLinkRepository(
                    new \Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceJobPaymentLinkModel(),
                );
            },
        );
        $this->app->singleton(
            \Modules\VehicleService\Application\Repositories\VehicleServiceJobInventoryLinkRepositoryInterface::class,
            function (): \Modules\VehicleService\Application\Repositories\VehicleServiceJobInventoryLinkRepositoryInterface {
                return new \Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehicleServiceJobInventoryLinkRepository(
                    new \Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceJobInventoryLinkModel(),
                );
            },
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../Persistence/Eloquent/Migrations');
    }
}