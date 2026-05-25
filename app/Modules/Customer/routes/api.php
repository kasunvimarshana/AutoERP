<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Customer\Presentation\Http\Controllers\CustomerController;
use Modules\Customer\Presentation\Http\Controllers\CustomerContactController;
use Modules\Customer\Presentation\Http\Controllers\CustomerAddressController;
use Modules\Customer\Presentation\Http\Controllers\CustomerVehicleController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);

Route::prefix('api/customer')
    ->middleware([
        'api',
        'auth:' . $protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
    ])
    ->name('customer.')
    ->group(function (): void {
        Route::apiResource('customers', CustomerController::class);
        Route::apiResource('customer-contacts', CustomerContactController::class);
        Route::apiResource('customer-addresses', CustomerAddressController::class);
        Route::apiResource('customer-vehicles', CustomerVehicleController::class);
    });