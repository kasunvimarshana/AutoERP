<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Tax\Constants\TaxPermission;
use Modules\Tax\Http\Controllers\TaxController;
use Modules\Tenant\Services\Plans\TenantPlanSchema;

$featureMiddleware = (string) config('tenant.entitlements.middleware_alias', 'tenant.feature');
$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit').':required',
    $featureMiddleware.':'.TenantPlanSchema::MODULE_FINANCE,
];
$permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');
$requires = static fn (string $permission): string => $permissionMiddleware.':'.$permission;

Route::prefix('api/v1/tax')->middleware($middleware)->name('api.v1.tax.')->group(function () use ($requires): void {
    Route::get('lookups', [TaxController::class, 'lookups'])
        ->middleware($requires(TaxPermission::LOOKUPS_VIEW))
        ->name('lookups');
    Route::post('calculate', [TaxController::class, 'calculate'])
        ->middleware($requires(TaxPermission::CALCULATIONS_RUN))
        ->name('calculate');

    Route::middleware($requires(TaxPermission::TAXES_VIEW))->group(function (): void {
        Route::get('taxes', [TaxController::class, 'taxes'])->name('taxes.index');
        Route::get('taxes/{tax}', [TaxController::class, 'showTax'])->whereNumber('tax')->name('taxes.show');
    });
    Route::middleware($requires(TaxPermission::TAXES_MANAGE))->group(function (): void {
        Route::post('taxes', [TaxController::class, 'storeTax'])->name('taxes.store');
        Route::patch('taxes/{tax}', [TaxController::class, 'updateTax'])->whereNumber('tax')->name('taxes.update');
        Route::post('taxes/{tax}/rates', [TaxController::class, 'addRate'])->whereNumber('tax')->name('taxes.rates.store');
    });

    Route::get('groups', [TaxController::class, 'groups'])
        ->middleware($requires(TaxPermission::GROUPS_VIEW))
        ->name('groups.index');
    Route::middleware($requires(TaxPermission::GROUPS_MANAGE))->group(function (): void {
        Route::post('groups', [TaxController::class, 'storeGroup'])->name('groups.store');
        Route::patch('groups/{group}', [TaxController::class, 'updateGroup'])->whereNumber('group')->name('groups.update');
    });

    Route::middleware($requires(TaxPermission::PROFILES_VIEW))->group(function (): void {
        Route::get('customer-profiles', [TaxController::class, 'customerProfiles'])->name('customer-profiles.index');
        Route::get('supplier-profiles', [TaxController::class, 'supplierProfiles'])->name('supplier-profiles.index');
    });
    Route::middleware($requires(TaxPermission::PROFILES_MANAGE))->group(function (): void {
        Route::post('customer-profiles', [TaxController::class, 'storeCustomerProfile'])->name('customer-profiles.store');
        Route::patch('customer-profiles/{profile}', [TaxController::class, 'updateCustomerProfile'])->whereNumber('profile')->name('customer-profiles.update');
        Route::post('supplier-profiles', [TaxController::class, 'storeSupplierProfile'])->name('supplier-profiles.store');
        Route::patch('supplier-profiles/{profile}', [TaxController::class, 'updateSupplierProfile'])->whereNumber('profile')->name('supplier-profiles.update');
    });

    Route::get('posting-profiles', [TaxController::class, 'postingProfiles'])
        ->middleware($requires(TaxPermission::POSTING_PROFILES_VIEW))
        ->name('posting-profiles.index');
    Route::middleware($requires(TaxPermission::POSTING_PROFILES_MANAGE))->group(function (): void {
        Route::post('posting-profiles', [TaxController::class, 'storePostingProfile'])->name('posting-profiles.store');
        Route::patch('posting-profiles/{profile}', [TaxController::class, 'updatePostingProfile'])->whereNumber('profile')->name('posting-profiles.update');
    });

    Route::get('reports/{report}', [TaxController::class, 'report'])
        ->where('report', '[A-Za-z0-9._-]+')
        ->middleware($requires(TaxPermission::REPORTS_VIEW))
        ->name('reports.show');
});
