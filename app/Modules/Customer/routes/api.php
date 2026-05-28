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
        Route::get('customers-lookup', [CustomerController::class, 'lookup'])->name('customers.lookup');
        Route::patch('customers/{customer}/status', [CustomerController::class, 'status'])->name('customers.status');
        Route::get('customers/{customer}/validate/sales', [CustomerController::class, 'validateForSales'])
            ->name('customers.validate.sales');
        Route::get(
            'customers/{customer}/validate/vehicle-service',
            [CustomerController::class, 'validateForVehicleService'],
        )
            ->name('customers.validate.vehicle-service');
        Route::get(
            'customers/{customer}/validate/vehicle-rental',
            [CustomerController::class, 'validateForVehicleRental'],
        )
            ->name('customers.validate.vehicle-rental');
        Route::get('customers/{customer}/finance-defaults', [CustomerController::class, 'financeDefaults'])
            ->name('customers.finance-defaults.show');
        Route::put('customers/{customer}/finance-defaults', [CustomerController::class, 'updateFinanceDefaults'])
            ->name('customers.finance-defaults.update');
        Route::post('customers/{customer}/credit-check', [CustomerController::class, 'creditCheck'])
            ->name('customers.credit-check');
        Route::get('customers/{customer}/tax-profile', [CustomerController::class, 'taxProfile'])
            ->name('customers.tax-profile.show');
        Route::get('customers/{customer}/user-accesses', [CustomerController::class, 'listUserAccesses'])
            ->name('customers.user-accesses.index');
        Route::post('customers/{customer}/user-accesses', [CustomerController::class, 'createUserAccess'])
            ->name('customers.user-accesses.store');
        Route::post('customers/{customer}/user-accesses/link-existing', [CustomerController::class, 'linkExistingUser'])
            ->name('customers.user-accesses.link-existing');
        Route::patch(
            'customers/{customer}/user-accesses/{access}/deactivate',
            [CustomerController::class, 'deactivateUserAccess'],
        )
            ->name('customers.user-accesses.deactivate');
        Route::delete('customers/{customer}/user-accesses/{access}', [CustomerController::class, 'unlinkUserAccess'])
            ->name('customers.user-accesses.destroy');

        Route::apiResource('customer-contacts', CustomerContactController::class);
        Route::apiResource('customer-addresses', CustomerAddressController::class);
        Route::apiResource('customer-vehicles', CustomerVehicleController::class);
    });
