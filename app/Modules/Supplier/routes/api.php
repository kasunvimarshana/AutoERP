<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Supplier\Presentation\Http\Controllers\SupplierController;
use Modules\Supplier\Presentation\Http\Controllers\SupplierContactController;
use Modules\Supplier\Presentation\Http\Controllers\SupplierAddressController;
use Modules\Supplier\Presentation\Http\Controllers\SupplierVehicleController;
use Modules\Supplier\Presentation\Http\Controllers\SupplierItemController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);

Route::prefix('api/supplier')
    ->middleware([
        'api',
        'auth:' . $protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
    ])
    ->name('supplier.')
    ->group(function (): void {
        Route::apiResource('suppliers', SupplierController::class);
        Route::apiResource('supplier-contacts', SupplierContactController::class);
        Route::apiResource('supplier-addresses', SupplierAddressController::class);
        Route::apiResource('supplier-vehicles', SupplierVehicleController::class);
        Route::apiResource('supplier-items', SupplierItemController::class);
    });