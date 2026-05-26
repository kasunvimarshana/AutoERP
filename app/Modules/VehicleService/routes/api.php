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
    });
