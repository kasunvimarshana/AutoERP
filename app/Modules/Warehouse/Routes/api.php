<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Warehouse\Http\Controllers\WarehouseController;
use Modules\Warehouse\Http\Controllers\WarehouseLocationController;
use Modules\Warehouse\Services\WarehouseAuthorizationService;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
).':required';
$permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');
$requires = static fn (string $permission): string => $permissionMiddleware.':'.$permission;

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
    ->group(function () use ($requires): void {
        Route::get('warehouses/default', [WarehouseController::class, 'defaultWarehouse'])
            ->middleware($requires(WarehouseAuthorizationService::WAREHOUSES_VIEW))
            ->name('warehouses.default');
        Route::patch('warehouses/{warehouse}/activate', [WarehouseController::class, 'activate'])
            ->whereNumber('warehouse')
            ->middleware($requires(WarehouseAuthorizationService::WAREHOUSES_ACTIVATE))
            ->name('warehouses.activate');
        Route::patch('warehouses/{warehouse}/deactivate', [WarehouseController::class, 'deactivate'])
            ->whereNumber('warehouse')
            ->middleware($requires(WarehouseAuthorizationService::WAREHOUSES_DEACTIVATE))
            ->name('warehouses.deactivate');
        Route::get('warehouses', [WarehouseController::class, 'index'])
            ->middleware($requires(WarehouseAuthorizationService::WAREHOUSES_VIEW))
            ->name('warehouses.index');
        Route::post('warehouses', [WarehouseController::class, 'store'])
            ->middleware($requires(WarehouseAuthorizationService::WAREHOUSES_CREATE))
            ->name('warehouses.store');
        Route::get('warehouses/{warehouse}', [WarehouseController::class, 'show'])
            ->whereNumber('warehouse')
            ->middleware($requires(WarehouseAuthorizationService::WAREHOUSES_VIEW))
            ->name('warehouses.show');
        Route::match(['put', 'patch'], 'warehouses/{warehouse}', [WarehouseController::class, 'update'])
            ->whereNumber('warehouse')
            ->middleware($requires(WarehouseAuthorizationService::WAREHOUSES_UPDATE))
            ->name('warehouses.update');
        Route::delete('warehouses/{warehouse}', [WarehouseController::class, 'destroy'])
            ->whereNumber('warehouse')
            ->middleware($requires(WarehouseAuthorizationService::WAREHOUSES_DELETE))
            ->name('warehouses.destroy');

        Route::get('warehouse-locations/default', [WarehouseLocationController::class, 'defaultLocation'])
            ->middleware($requires(WarehouseAuthorizationService::LOCATIONS_VIEW))
            ->name('warehouse-locations.default');
        Route::patch('warehouse-locations/{warehouseLocation}/activate', [WarehouseLocationController::class, 'activate'])
            ->whereNumber('warehouseLocation')
            ->middleware($requires(WarehouseAuthorizationService::LOCATIONS_ACTIVATE))
            ->name('warehouse-locations.activate');
        Route::patch('warehouse-locations/{warehouseLocation}/deactivate', [WarehouseLocationController::class, 'deactivate'])
            ->whereNumber('warehouseLocation')
            ->middleware($requires(WarehouseAuthorizationService::LOCATIONS_DEACTIVATE))
            ->name('warehouse-locations.deactivate');
        Route::get('warehouse-locations', [WarehouseLocationController::class, 'index'])
            ->middleware($requires(WarehouseAuthorizationService::LOCATIONS_VIEW))
            ->name('warehouse-locations.index');
        Route::post('warehouse-locations', [WarehouseLocationController::class, 'store'])
            ->middleware($requires(WarehouseAuthorizationService::LOCATIONS_CREATE))
            ->name('warehouse-locations.store');
        Route::get('warehouse-locations/{warehouseLocation}', [WarehouseLocationController::class, 'show'])
            ->whereNumber('warehouseLocation')
            ->middleware($requires(WarehouseAuthorizationService::LOCATIONS_VIEW))
            ->name('warehouse-locations.show');
        Route::match(['put', 'patch'], 'warehouse-locations/{warehouseLocation}', [WarehouseLocationController::class, 'update'])
            ->whereNumber('warehouseLocation')
            ->middleware($requires(WarehouseAuthorizationService::LOCATIONS_UPDATE))
            ->name('warehouse-locations.update');
        Route::delete('warehouse-locations/{warehouseLocation}', [WarehouseLocationController::class, 'destroy'])
            ->whereNumber('warehouseLocation')
            ->middleware($requires(WarehouseAuthorizationService::LOCATIONS_DELETE))
            ->name('warehouse-locations.destroy');
    });
