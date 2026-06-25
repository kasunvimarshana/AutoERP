<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Tax\Http\Controllers\TaxController;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit').':required',
];

Route::prefix('api/v1/tax')->middleware($middleware)->name('api.v1.tax.')->group(function (): void {
    Route::get('lookups', [TaxController::class, 'lookups'])->name('lookups');
    Route::post('calculate', [TaxController::class, 'calculate'])->name('calculate');

    Route::get('taxes', [TaxController::class, 'taxes'])->name('taxes.index');
    Route::post('taxes', [TaxController::class, 'storeTax'])->name('taxes.store');
    Route::get('taxes/{tax}', [TaxController::class, 'showTax'])->whereNumber('tax')->name('taxes.show');
    Route::patch('taxes/{tax}', [TaxController::class, 'updateTax'])->whereNumber('tax')->name('taxes.update');
    Route::post('taxes/{tax}/rates', [TaxController::class, 'addRate'])->whereNumber('tax')->name('taxes.rates.store');

    Route::get('groups', [TaxController::class, 'groups'])->name('groups.index');
    Route::post('groups', [TaxController::class, 'storeGroup'])->name('groups.store');
    Route::patch('groups/{group}', [TaxController::class, 'updateGroup'])->whereNumber('group')->name('groups.update');

    Route::get('customer-profiles', [TaxController::class, 'customerProfiles'])->name('customer-profiles.index');
    Route::post('customer-profiles', [TaxController::class, 'storeCustomerProfile'])->name('customer-profiles.store');
    Route::patch('customer-profiles/{profile}', [TaxController::class, 'updateCustomerProfile'])->whereNumber('profile')->name('customer-profiles.update');

    Route::get('supplier-profiles', [TaxController::class, 'supplierProfiles'])->name('supplier-profiles.index');
    Route::post('supplier-profiles', [TaxController::class, 'storeSupplierProfile'])->name('supplier-profiles.store');
    Route::patch('supplier-profiles/{profile}', [TaxController::class, 'updateSupplierProfile'])->whereNumber('profile')->name('supplier-profiles.update');

    Route::get('posting-profiles', [TaxController::class, 'postingProfiles'])->name('posting-profiles.index');
    Route::post('posting-profiles', [TaxController::class, 'storePostingProfile'])->name('posting-profiles.store');
    Route::patch('posting-profiles/{profile}', [TaxController::class, 'updatePostingProfile'])->whereNumber('profile')->name('posting-profiles.update');

    Route::get('reports/{report}', [TaxController::class, 'report'])
        ->where('report', '[A-Za-z0-9._-]+')
        ->name('reports.show');
});
