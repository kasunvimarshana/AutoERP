<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\VehicleService\Presentation\Http\Controllers\VehicleServiceTypeController;
use Modules\VehicleService\Presentation\Http\Controllers\VehicleServiceJobCardController;
use Modules\VehicleService\Presentation\Http\Controllers\VehicleServiceJobCardLineController;
use Modules\VehicleService\Presentation\Http\Controllers\VehicleServiceLaborItemController;
use Modules\VehicleService\Presentation\Http\Controllers\VehicleServiceNonInventoryItemController;
use Modules\VehicleService\Presentation\Http\Controllers\VehicleServiceLaborAssignmentController;
use Modules\VehicleService\Presentation\Http\Controllers\VehicleServiceDiagnosticController;
use Modules\VehicleService\Presentation\Http\Controllers\VehicleServiceDiagnosticLineController;
use Modules\VehicleService\Presentation\Http\Controllers\VehicleServiceInspectionController;
use Modules\VehicleService\Presentation\Http\Controllers\VehicleServiceInspectionLineController;
use Modules\VehicleService\Presentation\Http\Controllers\VehicleServiceIntegrationController;
use Modules\VehicleService\Presentation\Http\Controllers\VehicleServiceManagementController;
use Modules\VehicleService\Presentation\Http\Controllers\VehicleServiceWorkflowController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);

Route::prefix('api/vehicle-service')
    ->middleware([
        'api',
        'auth:' . $protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
    ])
    ->name('vehicleservice.')
    ->group(function (): void {
        Route::apiResource('vehicle-service-types', VehicleServiceTypeController::class)
            ->parameters(['vehicle-service-types' => 'service_type']);
        Route::apiResource('vehicle-service-job-cards', VehicleServiceJobCardController::class)
            ->parameters(['vehicle-service-job-cards' => 'job_card']);
        Route::apiResource(
            'vehicle-service-job-card-lines',
            VehicleServiceJobCardLineController::class,
        )->parameters(['vehicle-service-job-card-lines' => 'job_card_line']);
        Route::apiResource('vehicle-service-labor-items', VehicleServiceLaborItemController::class)
            ->parameters(['vehicle-service-labor-items' => 'labor_item']);
        Route::apiResource(
            'vehicle-service-non-inventory-items',
            VehicleServiceNonInventoryItemController::class,
        )->parameters(['vehicle-service-non-inventory-items' => 'non_inventory_item']);
        Route::apiResource(
            'vehicle-service-labor-assignments',
            VehicleServiceLaborAssignmentController::class,
        )->parameters(['vehicle-service-labor-assignments' => 'labor_assignment']);
        Route::apiResource('vehicle-service-diagnostics', VehicleServiceDiagnosticController::class)
            ->parameters(['vehicle-service-diagnostics' => 'diagnostic']);
        Route::apiResource(
            'vehicle-service-diagnostic-lines',
            VehicleServiceDiagnosticLineController::class,
        )->parameters(['vehicle-service-diagnostic-lines' => 'diagnostic_line']);
        Route::apiResource('vehicle-service-inspections', VehicleServiceInspectionController::class)
            ->parameters(['vehicle-service-inspections' => 'inspection']);
        Route::apiResource(
            'vehicle-service-inspection-lines',
            VehicleServiceInspectionLineController::class,
        )->parameters(['vehicle-service-inspection-lines' => 'inspection_line']);

        Route::post(
            'job-cards/aggregate',
            [VehicleServiceManagementController::class, 'upsertJobCardAggregate'],
        )->name('job-cards.aggregate.store');
        Route::put(
            'job-cards/{jobCardId}/aggregate',
            [VehicleServiceManagementController::class, 'updateJobCardAggregate'],
        )->name('job-cards.aggregate.update');
        Route::post(
            'job-cards/{jobCardId}/lines/sync',
            [VehicleServiceManagementController::class, 'syncJobCardLines'],
        )->name('job-cards.lines.sync');
        Route::post(
            'job-cards/{jobCardId}/labor-items/sync',
            [VehicleServiceManagementController::class, 'syncLaborItems'],
        )->name('job-cards.labor-items.sync');
        Route::post(
            'job-cards/{jobCardId}/external-services/sync',
            [VehicleServiceManagementController::class, 'syncExternalServices'],
        )->name('job-cards.external-services.sync');
        Route::post(
            'job-cards/{jobCardId}/customer-supplied-items/sync',
            [VehicleServiceManagementController::class, 'syncCustomerSuppliedItems'],
        )->name('job-cards.customer-supplied-items.sync');

        Route::get(
            'settings',
            [VehicleServiceManagementController::class, 'showSettings'],
        )->name('settings.show');
        Route::post(
            'settings',
            [VehicleServiceManagementController::class, 'upsertSettings'],
        )->name('settings.upsert');
        Route::post(
            'settings/initialize',
            [VehicleServiceManagementController::class, 'initializeSettings'],
        )->name('settings.initialize');
        Route::get(
            'status-history/{entityType}/{entityId}',
            [VehicleServiceManagementController::class, 'statusHistory'],
        )->name('status-history.show');
        Route::get(
            'stock-availability',
            [VehicleServiceManagementController::class, 'stockAvailability'],
        )->name('stock-availability.show');
        Route::get(
            'invoiceable-job-cards',
            [VehicleServiceManagementController::class, 'invoiceableJobCards'],
        )->name('job-cards.invoiceable');
        Route::get(
            'receivable-job-cards',
            [VehicleServiceManagementController::class, 'receivableJobCards'],
        )->name('job-cards.receivable');

        Route::post(
            'workflow/job-cards/{jobCardId}/transition',
            [VehicleServiceWorkflowController::class, 'transition'],
        )->name('workflow.transition');
        Route::post(
            'workflow/job-cards/{jobCardId}/invoice',
            [VehicleServiceWorkflowController::class, 'createInvoice'],
        )->name('workflow.invoice');
        Route::post(
            'workflow/job-cards/{jobCardId}/payments/allocate',
            [VehicleServiceWorkflowController::class, 'allocatePayment'],
        )->name('workflow.payments.allocate');
        Route::post(
            'workflow/job-cards/{jobCardId}/inventory/post',
            [VehicleServiceWorkflowController::class, 'postInventory'],
        )->name('workflow.inventory.post');
        Route::post(
            'workflow/job-cards/{jobCardId}/finance/post',
            [VehicleServiceWorkflowController::class, 'postFinance'],
        )->name('workflow.finance.post');
        Route::post(
            'workflow/job-cards/{jobCardId}/finance/reverse',
            [VehicleServiceWorkflowController::class, 'reverseFinance'],
        )->name('workflow.finance.reverse');

        Route::post(
            'integration/job-cards/{jobCardId}/invoice',
            [VehicleServiceIntegrationController::class, 'createServiceInvoice'],
        )->name('integration.invoice');
        Route::post(
            'integration/job-cards/{jobCardId}/payments/allocate',
            [VehicleServiceIntegrationController::class, 'allocateServicePayment'],
        )->name('integration.payments.allocate');
        Route::post(
            'integration/job-cards/{jobCardId}/inventory/post',
            [VehicleServiceIntegrationController::class, 'postServiceInventory'],
        )->name('integration.inventory.post');
    });
