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
        Route::apiResource('vehicle-service-types', VehicleServiceTypeController::class);
        Route::apiResource('vehicle-service-job-cards', VehicleServiceJobCardController::class);
        Route::apiResource('vehicle-service-job-card-lines', VehicleServiceJobCardLineController::class);
        Route::apiResource('vehicle-service-labor-items', VehicleServiceLaborItemController::class);
        Route::apiResource('vehicle-service-non-inventory-items', VehicleServiceNonInventoryItemController::class);
        Route::apiResource('vehicle-service-labor-assignments', VehicleServiceLaborAssignmentController::class);
        Route::apiResource('vehicle-service-diagnostics', VehicleServiceDiagnosticController::class);
        Route::apiResource('vehicle-service-diagnostic-lines', VehicleServiceDiagnosticLineController::class);
        Route::apiResource('vehicle-service-inspections', VehicleServiceInspectionController::class);
        Route::apiResource('vehicle-service-inspection-lines', VehicleServiceInspectionLineController::class);
    });