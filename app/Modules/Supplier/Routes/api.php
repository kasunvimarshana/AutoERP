<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Supplier\Http\Controllers\SupplierCategoryController;
use Modules\Supplier\Http\Controllers\SupplierController;
use Modules\Supplier\Http\Controllers\SupplierRelationController;
use Modules\Supplier\Http\Controllers\SupplierVehicleController;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit').':required',
    'tenant.feature:supplier',
];

Route::prefix('api/v1')->middleware($middleware)->name('api.v1.')->group(function (): void {
    Route::get('supplier-vehicles', [SupplierVehicleController::class, 'index']);
    Route::post('supplier-vehicles', [SupplierVehicleController::class, 'store']);
    Route::get('supplier-vehicles/{relationship}', [SupplierVehicleController::class, 'show'])->whereNumber('relationship');
    Route::match(['put', 'patch'], 'supplier-vehicles/{relationship}', [SupplierVehicleController::class, 'update'])->whereNumber('relationship');
    Route::post('supplier-vehicles/{relationship}/set-current', [SupplierVehicleController::class, 'setCurrent'])->whereNumber('relationship');
    Route::post('supplier-vehicles/{relationship}/clear-current', [SupplierVehicleController::class, 'clearCurrent'])->whereNumber('relationship');
    Route::delete('supplier-vehicles/{relationship}', [SupplierVehicleController::class, 'destroy'])->whereNumber('relationship');
    Route::get('suppliers/lookup/{kind?}', [SupplierController::class, 'lookup'])
        ->whereIn('kind', ['active', 'credit-allowed', 'by-item'])
        ->name('suppliers.lookup');
    Route::post('suppliers/with-relations', [SupplierController::class, 'storeWithRelations'])->name('suppliers.with-relations.store');
    Route::patch('suppliers/{supplier}/activate', [SupplierController::class, 'activate'])->whereNumber('supplier')->name('suppliers.activate');
    Route::patch('suppliers/{supplier}/deactivate', [SupplierController::class, 'deactivate'])->whereNumber('supplier')->name('suppliers.deactivate');
    Route::patch('suppliers/{supplier}/status', [SupplierController::class, 'changeStatus'])->whereNumber('supplier')->name('suppliers.status');

    Route::get('suppliers/{supplier}/contacts', [SupplierRelationController::class, 'contacts'])->whereNumber('supplier');
    Route::post('suppliers/{supplier}/contacts', [SupplierRelationController::class, 'storeContact'])->whereNumber('supplier');
    Route::put('suppliers/{supplier}/contacts/{contact}', [SupplierRelationController::class, 'updateContact'])->whereNumber(['supplier', 'contact']);
    Route::delete('suppliers/{supplier}/contacts/{contact}', [SupplierRelationController::class, 'deleteContact'])->whereNumber(['supplier', 'contact']);

    Route::get('suppliers/{supplier}/addresses', [SupplierRelationController::class, 'addresses'])->whereNumber('supplier');
    Route::post('suppliers/{supplier}/addresses', [SupplierRelationController::class, 'storeAddress'])->whereNumber('supplier');
    Route::put('suppliers/{supplier}/addresses/{address}', [SupplierRelationController::class, 'updateAddress'])->whereNumber(['supplier', 'address']);
    Route::delete('suppliers/{supplier}/addresses/{address}', [SupplierRelationController::class, 'deleteAddress'])->whereNumber(['supplier', 'address']);

    Route::get('suppliers/{supplier}/bank-accounts', [SupplierRelationController::class, 'bankAccounts'])->whereNumber('supplier');
    Route::post('suppliers/{supplier}/bank-accounts', [SupplierRelationController::class, 'storeBankAccount'])->whereNumber('supplier');
    Route::put('suppliers/{supplier}/bank-accounts/{bankAccount}', [SupplierRelationController::class, 'updateBankAccount'])->whereNumber(['supplier', 'bankAccount']);
    Route::delete('suppliers/{supplier}/bank-accounts/{bankAccount}', [SupplierRelationController::class, 'deleteBankAccount'])->whereNumber(['supplier', 'bankAccount']);

    Route::get('suppliers/{supplier}/categories', [SupplierRelationController::class, 'categories'])->whereNumber('supplier');
    Route::post('suppliers/{supplier}/categories', [SupplierRelationController::class, 'assignCategory'])->whereNumber('supplier');
    Route::delete('suppliers/{supplier}/categories/{category}', [SupplierRelationController::class, 'deleteCategory'])->whereNumber(['supplier', 'category']);

    Route::get('suppliers/{supplier}/documents', [SupplierRelationController::class, 'documents'])->whereNumber('supplier');
    Route::post('suppliers/{supplier}/documents', [SupplierRelationController::class, 'storeDocument'])->whereNumber('supplier');
    Route::put('suppliers/{supplier}/documents/{document}', [SupplierRelationController::class, 'updateDocument'])->whereNumber(['supplier', 'document']);
    Route::delete('suppliers/{supplier}/documents/{document}', [SupplierRelationController::class, 'deleteDocument'])->whereNumber(['supplier', 'document']);

    Route::get('suppliers/{supplier}/item-mappings', [SupplierRelationController::class, 'itemMappings'])->whereNumber('supplier');
    Route::post('suppliers/{supplier}/item-mappings', [SupplierRelationController::class, 'storeItemMapping'])->whereNumber('supplier');
    Route::put('suppliers/{supplier}/item-mappings/{mapping}', [SupplierRelationController::class, 'updateItemMapping'])->whereNumber(['supplier', 'mapping']);
    Route::delete('suppliers/{supplier}/item-mappings/{mapping}', [SupplierRelationController::class, 'deleteItemMapping'])->whereNumber(['supplier', 'mapping']);

    Route::get('suppliers/{supplier}/credit-profile', [SupplierRelationController::class, 'creditProfile'])->whereNumber('supplier');
    Route::put('suppliers/{supplier}/credit-profile', [SupplierRelationController::class, 'updateCreditProfile'])->whereNumber('supplier');
    Route::get('suppliers/{supplier}/status-history', [SupplierRelationController::class, 'statusHistory'])->whereNumber('supplier');

    Route::apiResource('suppliers', SupplierController::class);

    Route::get('supplier-categories/lookup', [SupplierCategoryController::class, 'lookup'])->name('supplier-categories.lookup');
    Route::apiResource('supplier-categories', SupplierCategoryController::class);
});
