<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Supplier\Presentation\Http\Controllers\SupplierBankAccountController;
use Modules\Supplier\Presentation\Http\Controllers\SupplierCategoryController;
use Modules\Supplier\Presentation\Http\Controllers\SupplierController;
use Modules\Supplier\Presentation\Http\Controllers\SupplierAddressController;
use Modules\Supplier\Presentation\Http\Controllers\SupplierContactController;
use Modules\Supplier\Presentation\Http\Controllers\SupplierItemController;
use Modules\Supplier\Presentation\Http\Controllers\SupplierTaxProfileController;
use Modules\Supplier\Presentation\Http\Controllers\SupplierVehicleController;

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
        Route::get('suppliers-lookup', [SupplierController::class, 'lookup'])->name('suppliers.lookup');
        Route::patch('suppliers/{supplier}/status', [SupplierController::class, 'status'])->name('suppliers.status');
        Route::get('suppliers/{supplier}/validate-for-purchase', [SupplierController::class, 'validateForPurchase'])
            ->name('suppliers.validate-for-purchase');
        Route::get('suppliers/{supplier}/finance-defaults', [SupplierController::class, 'financeDefaults'])
            ->name('suppliers.finance-defaults');
        Route::put('suppliers/{supplier}/finance-defaults', [SupplierController::class, 'updateFinanceDefaults'])
            ->name('suppliers.finance-defaults.update');
        Route::get('suppliers/{supplier}/user-accesses', [SupplierController::class, 'listUserAccesses'])
            ->name('suppliers.user-accesses.index');
        Route::post('suppliers/{supplier}/user-accesses', [SupplierController::class, 'createUserAccess'])
            ->name('suppliers.user-accesses.store');
        Route::post('suppliers/{supplier}/link-user', [SupplierController::class, 'linkExistingUser'])
            ->name('suppliers.user-accesses.link-existing');
        Route::patch(
            'suppliers/{supplier}/user-accesses/{access}/deactivate',
            [SupplierController::class, 'deactivateUserAccess'],
        )
            ->name('suppliers.user-accesses.deactivate');
        Route::delete('suppliers/{supplier}/user-accesses/{access}', [SupplierController::class, 'unlinkUserAccess'])
            ->name('suppliers.user-accesses.destroy');

        Route::get('supplier-categories', [SupplierCategoryController::class, 'index'])
            ->name('supplier-categories.index');
        Route::post('supplier-categories', [SupplierCategoryController::class, 'store'])
            ->name('supplier-categories.store');
        Route::put('supplier-categories/{id}', [SupplierCategoryController::class, 'update'])
            ->name('supplier-categories.update');
        Route::delete('supplier-categories/{id}', [SupplierCategoryController::class, 'destroy'])
            ->name('supplier-categories.destroy');

        Route::get('suppliers/{supplier}/bank-accounts', [SupplierBankAccountController::class, 'index'])
            ->name('suppliers.bank-accounts.index');
        Route::post('suppliers/{supplier}/bank-accounts', [SupplierBankAccountController::class, 'store'])
            ->name('suppliers.bank-accounts.store');
        Route::put('suppliers/{supplier}/bank-accounts/{id}', [SupplierBankAccountController::class, 'update'])
            ->name('suppliers.bank-accounts.update');
        Route::delete('suppliers/{supplier}/bank-accounts/{id}', [SupplierBankAccountController::class, 'destroy'])
            ->name('suppliers.bank-accounts.destroy');

        Route::get('suppliers/{supplier}/tax-profile', [SupplierTaxProfileController::class, 'show'])
            ->name('suppliers.tax-profile.show');
        Route::put('suppliers/{supplier}/tax-profile', [SupplierTaxProfileController::class, 'upsert'])
            ->name('suppliers.tax-profile.upsert');
        Route::patch('suppliers/{supplier}/tax-profile/deactivate', [SupplierTaxProfileController::class, 'deactivate'])
            ->name('suppliers.tax-profile.deactivate');

        Route::apiResource('supplier-contacts', SupplierContactController::class);
        Route::apiResource('supplier-addresses', SupplierAddressController::class);
        Route::apiResource('supplier-vehicles', SupplierVehicleController::class);
        Route::apiResource('supplier-items', SupplierItemController::class);
    });
