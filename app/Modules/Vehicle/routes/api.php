<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Vehicle\Presentation\Http\Controllers\VehicleController;
use Modules\Vehicle\Presentation\Http\Controllers\VehicleDocumentController;
use Modules\Vehicle\Presentation\Http\Controllers\VehicleOwnershipController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);

Route::prefix('api/vehicle')
    ->middleware([
        'api',
        'auth:' . $protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
    ])
    ->name('vehicle.')
    ->group(function (): void {
        Route::apiResource('vehicles', VehicleController::class);
        Route::get('vehicles/{vehicle}/ownerships/current', [VehicleOwnershipController::class, 'current'])
            ->name('vehicles.ownerships.current');
        Route::post('vehicles/{vehicle}/ownerships/{ownership}/end', [VehicleOwnershipController::class, 'end'])
            ->name('vehicles.ownerships.end');
        Route::post('vehicles/{vehicle}/ownerships/{ownership}/set-current', [VehicleOwnershipController::class, 'setCurrent'])
            ->name('vehicles.ownerships.set-current');
        Route::apiResource('vehicles.ownerships', VehicleOwnershipController::class)
            ->only(['index', 'store', 'update'])
            ->parameters(['vehicles' => 'vehicle', 'ownerships' => 'ownership']);
        Route::apiResource('vehicle-documents', VehicleDocumentController::class);
    });
