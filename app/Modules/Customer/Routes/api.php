<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Customer\Http\Controllers\CustomerCategoryController;
use Modules\Customer\Http\Controllers\CustomerController;
use Modules\Customer\Http\Controllers\CustomerRelationController;
use Modules\Customer\Http\Controllers\CustomerVehicleController;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit'),
    'tenant.feature:customer',
];

Route::prefix('api/v1')->middleware($middleware)->name('api.v1.')->group(function (): void {
    Route::get('customer-vehicles', [CustomerVehicleController::class, 'index']);
    Route::post('customer-vehicles', [CustomerVehicleController::class, 'store']);
    Route::get('customer-vehicles/{relationship}', [CustomerVehicleController::class, 'show'])->whereNumber('relationship');
    Route::match(['put', 'patch'], 'customer-vehicles/{relationship}', [CustomerVehicleController::class, 'update'])->whereNumber('relationship');
    Route::post('customer-vehicles/{relationship}/set-current', [CustomerVehicleController::class, 'setCurrent'])->whereNumber('relationship');
    Route::post('customer-vehicles/{relationship}/clear-current', [CustomerVehicleController::class, 'clearCurrent'])->whereNumber('relationship');
    Route::delete('customer-vehicles/{relationship}', [CustomerVehicleController::class, 'destroy'])->whereNumber('relationship');
    Route::get('customers/lookup/{kind?}', [CustomerController::class, 'lookup'])
        ->whereIn('kind', ['active', 'credit-allowed'])
        ->name('customers.lookup');
    Route::post('customers/with-relations', [CustomerController::class, 'storeWithRelations'])->name('customers.with-relations.store');
    Route::patch('customers/{customer}/activate', [CustomerController::class, 'activate'])->whereNumber('customer')->name('customers.activate');
    Route::patch('customers/{customer}/deactivate', [CustomerController::class, 'deactivate'])->whereNumber('customer')->name('customers.deactivate');
    Route::patch('customers/{customer}/status', [CustomerController::class, 'changeStatus'])->whereNumber('customer')->name('customers.status');

    Route::get('customers/{customer}/contacts', [CustomerRelationController::class, 'contacts'])->whereNumber('customer');
    Route::post('customers/{customer}/contacts', [CustomerRelationController::class, 'storeContact'])->whereNumber('customer');
    Route::put('customers/{customer}/contacts/{contact}', [CustomerRelationController::class, 'updateContact'])->whereNumber(['customer', 'contact']);
    Route::delete('customers/{customer}/contacts/{contact}', [CustomerRelationController::class, 'deleteContact'])->whereNumber(['customer', 'contact']);

    Route::get('customers/{customer}/addresses', [CustomerRelationController::class, 'addresses'])->whereNumber('customer');
    Route::post('customers/{customer}/addresses', [CustomerRelationController::class, 'storeAddress'])->whereNumber('customer');
    Route::put('customers/{customer}/addresses/{address}', [CustomerRelationController::class, 'updateAddress'])->whereNumber(['customer', 'address']);
    Route::delete('customers/{customer}/addresses/{address}', [CustomerRelationController::class, 'deleteAddress'])->whereNumber(['customer', 'address']);

    Route::get('customers/{customer}/bank-accounts', [CustomerRelationController::class, 'bankAccounts'])->whereNumber('customer');
    Route::post('customers/{customer}/bank-accounts', [CustomerRelationController::class, 'storeBankAccount'])->whereNumber('customer');
    Route::put('customers/{customer}/bank-accounts/{bankAccount}', [CustomerRelationController::class, 'updateBankAccount'])->whereNumber(['customer', 'bankAccount']);
    Route::delete('customers/{customer}/bank-accounts/{bankAccount}', [CustomerRelationController::class, 'deleteBankAccount'])->whereNumber(['customer', 'bankAccount']);

    Route::get('customers/{customer}/categories', [CustomerRelationController::class, 'categories'])->whereNumber('customer');
    Route::post('customers/{customer}/categories', [CustomerRelationController::class, 'assignCategory'])->whereNumber('customer');
    Route::delete('customers/{customer}/categories/{category}', [CustomerRelationController::class, 'deleteCategory'])->whereNumber(['customer', 'category']);

    Route::get('customers/{customer}/documents', [CustomerRelationController::class, 'documents'])->whereNumber('customer');
    Route::post('customers/{customer}/documents', [CustomerRelationController::class, 'storeDocument'])->whereNumber('customer');
    Route::put('customers/{customer}/documents/{document}', [CustomerRelationController::class, 'updateDocument'])->whereNumber(['customer', 'document']);
    Route::delete('customers/{customer}/documents/{document}', [CustomerRelationController::class, 'deleteDocument'])->whereNumber(['customer', 'document']);

    Route::get('customers/{customer}/credit-profile', [CustomerRelationController::class, 'creditProfile'])->whereNumber('customer');
    Route::put('customers/{customer}/credit-profile', [CustomerRelationController::class, 'updateCreditProfile'])->whereNumber('customer');
    Route::get('customers/{customer}/status-history', [CustomerRelationController::class, 'statusHistory'])->whereNumber('customer');

    Route::apiResource('customers', CustomerController::class);

    Route::get('customer-categories/lookup', [CustomerCategoryController::class, 'lookup'])->name('customer-categories.lookup');
    Route::apiResource('customer-categories', CustomerCategoryController::class);
});
