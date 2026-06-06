<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Warehouse\Http\Controllers\WarehouseController;
use Modules\Warehouse\Http\Controllers\WarehouseLocationController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);

Route::prefix('api/warehouse')
    ->middleware([
        'api',
        'auth:'.$protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
    ])
    ->name('warehouse.')
    ->group(function (): void {
        Route::apiResource('warehouses', WarehouseController::class);
        Route::apiResource('warehouse-locations', WarehouseLocationController::class);
    });
