<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Supplier\Http\Controllers\SupplierCategoryController;
use Modules\Supplier\Http\Controllers\SupplierController;
use Modules\Supplier\Http\Controllers\SupplierRelationController;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit').':required',
    'tenant.feature:supplier',
];

Route::prefix('api/v1')->middleware($middleware)->name('api.v1.')->group(function (): void {

    Route::get('suppliers/lookup/{kind?}', [SupplierController::class, 'lookup'])
        ->whereIn('kind', ['active', 'credit-allowed', 'by-item'])
        ->name('suppliers.lookup');
    Route::post('suppliers/with-relations', [SupplierController::class, 'storeWithRelations'])
        ->name('suppliers.with-relations.store');
    Route::patch('suppliers/{supplier}/activate', [SupplierController::class, 'activate'])
        ->whereNumber('supplier')
        ->name('suppliers.activate');
    Route::patch('suppliers/{supplier}/deactivate', [SupplierController::class, 'deactivate'])
        ->whereNumber('supplier')
        ->name('suppliers.deactivate');
    Route::patch('suppliers/{supplier}/status', [SupplierController::class, 'changeStatus'])
        ->whereNumber('supplier')
        ->name('suppliers.status');

    Route::prefix('suppliers/{supplier}')->name('suppliers.')->group(function (): void {
        Route::get('contacts', [SupplierRelationController::class, 'contacts'])
            ->whereNumber('supplier')
            ->name('contacts.index');
        Route::post('contacts', [SupplierRelationController::class, 'storeContact'])
            ->whereNumber('supplier')
            ->name('contacts.store');
        Route::put('contacts/{contact}', [SupplierRelationController::class, 'updateContact'])
            ->whereNumber(['supplier', 'contact'])
            ->name('contacts.update');
        Route::delete('contacts/{contact}', [SupplierRelationController::class, 'deleteContact'])
            ->whereNumber(['supplier', 'contact'])
            ->name('contacts.destroy');

        Route::get('addresses', [SupplierRelationController::class, 'addresses'])
            ->whereNumber('supplier')
            ->name('addresses.index');
        Route::post('addresses', [SupplierRelationController::class, 'storeAddress'])
            ->whereNumber('supplier')
            ->name('addresses.store');
        Route::put('addresses/{address}', [SupplierRelationController::class, 'updateAddress'])
            ->whereNumber(['supplier', 'address'])
            ->name('addresses.update');
        Route::delete('addresses/{address}', [SupplierRelationController::class, 'deleteAddress'])
            ->whereNumber(['supplier', 'address'])
            ->name('addresses.destroy');

        Route::get('bank-accounts', [SupplierRelationController::class, 'bankAccounts'])
            ->whereNumber('supplier')
            ->name('bank-accounts.index');
        Route::post('bank-accounts', [SupplierRelationController::class, 'storeBankAccount'])
            ->whereNumber('supplier')
            ->name('bank-accounts.store');
        Route::put('bank-accounts/{bankAccount}', [SupplierRelationController::class, 'updateBankAccount'])
            ->whereNumber(['supplier', 'bankAccount'])
            ->name('bank-accounts.update');
        Route::delete('bank-accounts/{bankAccount}', [SupplierRelationController::class, 'deleteBankAccount'])
            ->whereNumber(['supplier', 'bankAccount'])
            ->name('bank-accounts.destroy');

        Route::get('categories', [SupplierRelationController::class, 'categories'])
            ->whereNumber('supplier')
            ->name('categories.index');
        Route::post('categories', [SupplierRelationController::class, 'assignCategory'])
            ->whereNumber('supplier')
            ->name('categories.store');
        Route::delete('categories/{category}', [SupplierRelationController::class, 'deleteCategory'])
            ->whereNumber(['supplier', 'category'])
            ->name('categories.destroy');

        Route::get('documents', [SupplierRelationController::class, 'documents'])
            ->whereNumber('supplier')
            ->name('documents.index');
        Route::post('documents', [SupplierRelationController::class, 'storeDocument'])
            ->whereNumber('supplier')
            ->name('documents.store');
        Route::put('documents/{document}', [SupplierRelationController::class, 'updateDocument'])
            ->whereNumber(['supplier', 'document'])
            ->name('documents.update');
        Route::delete('documents/{document}', [SupplierRelationController::class, 'deleteDocument'])
            ->whereNumber(['supplier', 'document'])
            ->name('documents.destroy');

        Route::get('item-mappings', [SupplierRelationController::class, 'itemMappings'])
            ->whereNumber('supplier')
            ->name('item-mappings.index');
        Route::post('item-mappings', [SupplierRelationController::class, 'storeItemMapping'])
            ->whereNumber('supplier')
            ->name('item-mappings.store');
        Route::put('item-mappings/{mapping}', [SupplierRelationController::class, 'updateItemMapping'])
            ->whereNumber(['supplier', 'mapping'])
            ->name('item-mappings.update');
        Route::delete('item-mappings/{mapping}', [SupplierRelationController::class, 'deleteItemMapping'])
            ->whereNumber(['supplier', 'mapping'])
            ->name('item-mappings.destroy');

        Route::get('credit-profile', [SupplierRelationController::class, 'creditProfile'])
            ->whereNumber('supplier')
            ->name('credit-profile.show');
        Route::put('credit-profile', [SupplierRelationController::class, 'updateCreditProfile'])
            ->whereNumber('supplier')
            ->name('credit-profile.update');
        Route::get('status-history', [SupplierRelationController::class, 'statusHistory'])
            ->whereNumber('supplier')
            ->name('status-history.index');
    });

    Route::apiResource('suppliers', SupplierController::class);

    Route::get('supplier-categories/lookup', [SupplierCategoryController::class, 'lookup'])
        ->name('supplier-categories.lookup');
    Route::apiResource('supplier-categories', SupplierCategoryController::class);
});
