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

Route::prefix('api/v1')
    ->middleware([
        'api',
        'auth:'.$protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
        'tenant.feature:warehouse',
    ])
    ->name('api.v1.')
    ->group(function (): void {
        Route::get('warehouses/default', [WarehouseController::class, 'defaultWarehouse'])
            ->name('warehouses.default');
        Route::patch('warehouses/{warehouse}/activate', [WarehouseController::class, 'activate'])
            ->whereNumber('warehouse')
            ->name('warehouses.activate');
        Route::patch('warehouses/{warehouse}/deactivate', [WarehouseController::class, 'deactivate'])
            ->whereNumber('warehouse')
            ->name('warehouses.deactivate');
        Route::apiResource('warehouses', WarehouseController::class);

        Route::get('warehouse-locations/default', [WarehouseLocationController::class, 'defaultLocation'])
            ->name('warehouse-locations.default');
        Route::patch('warehouse-locations/{warehouseLocation}/activate', [WarehouseLocationController::class, 'activate'])
            ->whereNumber('warehouseLocation')
            ->name('warehouse-locations.activate');
        Route::patch('warehouse-locations/{warehouseLocation}/deactivate', [WarehouseLocationController::class, 'deactivate'])
            ->whereNumber('warehouseLocation')
            ->name('warehouse-locations.deactivate');
        Route::apiResource('warehouse-locations', WarehouseLocationController::class);
    });
