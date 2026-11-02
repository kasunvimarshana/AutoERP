<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Customer\Http\Controllers\CustomerCategoryController;
use Modules\Customer\Http\Controllers\CustomerController;
use Modules\Customer\Http\Controllers\CustomerRelationController;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit').':required',
    'tenant.feature:customer',
];

Route::prefix('api/v1')->middleware($middleware)->name('api.v1.')->group(function (): void {
    Route::get('customers/lookup/{kind?}', [CustomerController::class, 'lookup'])
        ->whereIn('kind', ['active', 'credit-allowed'])
        ->name('customers.lookup');
    Route::post('customers/with-relations', [CustomerController::class, 'storeWithRelations'])
        ->name('customers.with-relations.store');
    Route::patch('customers/{customer}/activate', [CustomerController::class, 'activate'])
        ->whereNumber('customer')
        ->name('customers.activate');
    Route::patch('customers/{customer}/deactivate', [CustomerController::class, 'deactivate'])
        ->whereNumber('customer')
        ->name('customers.deactivate');
    Route::patch('customers/{customer}/status', [CustomerController::class, 'changeStatus'])
        ->whereNumber('customer')
        ->name('customers.status');

    Route::prefix('customers/{customer}')->name('customers.')->group(function (): void {
        Route::get('contacts', [CustomerRelationController::class, 'contacts'])
            ->whereNumber('customer')
            ->name('contacts.index');
        Route::post('contacts', [CustomerRelationController::class, 'storeContact'])
            ->whereNumber('customer')
            ->name('contacts.store');
        Route::put('contacts/{contact}', [CustomerRelationController::class, 'updateContact'])
            ->whereNumber(['customer', 'contact'])
            ->name('contacts.update');
        Route::delete('contacts/{contact}', [CustomerRelationController::class, 'deleteContact'])
            ->whereNumber(['customer', 'contact'])
            ->name('contacts.destroy');

        Route::get('addresses', [CustomerRelationController::class, 'addresses'])
            ->whereNumber('customer')
            ->name('addresses.index');
        Route::post('addresses', [CustomerRelationController::class, 'storeAddress'])
            ->whereNumber('customer')
            ->name('addresses.store');
        Route::put('addresses/{address}', [CustomerRelationController::class, 'updateAddress'])
            ->whereNumber(['customer', 'address'])
            ->name('addresses.update');
        Route::delete('addresses/{address}', [CustomerRelationController::class, 'deleteAddress'])
            ->whereNumber(['customer', 'address'])
            ->name('addresses.destroy');

        Route::get('bank-accounts', [CustomerRelationController::class, 'bankAccounts'])
            ->whereNumber('customer')
            ->name('bank-accounts.index');
        Route::post('bank-accounts', [CustomerRelationController::class, 'storeBankAccount'])
            ->whereNumber('customer')
            ->name('bank-accounts.store');
        Route::put('bank-accounts/{bankAccount}', [CustomerRelationController::class, 'updateBankAccount'])
            ->whereNumber(['customer', 'bankAccount'])
            ->name('bank-accounts.update');
        Route::delete('bank-accounts/{bankAccount}', [CustomerRelationController::class, 'deleteBankAccount'])
            ->whereNumber(['customer', 'bankAccount'])
            ->name('bank-accounts.destroy');

        Route::get('categories', [CustomerRelationController::class, 'categories'])
            ->whereNumber('customer')
            ->name('categories.index');
        Route::post('categories', [CustomerRelationController::class, 'assignCategory'])
            ->whereNumber('customer')
            ->name('categories.store');
        Route::delete('categories/{category}', [CustomerRelationController::class, 'deleteCategory'])
            ->whereNumber(['customer', 'category'])
            ->name('categories.destroy');

        Route::get('documents', [CustomerRelationController::class, 'documents'])
            ->whereNumber('customer')
            ->name('documents.index');
        Route::post('documents', [CustomerRelationController::class, 'storeDocument'])
            ->whereNumber('customer')
            ->name('documents.store');
        Route::put('documents/{document}', [CustomerRelationController::class, 'updateDocument'])
            ->whereNumber(['customer', 'document'])
            ->name('documents.update');
        Route::delete('documents/{document}', [CustomerRelationController::class, 'deleteDocument'])
            ->whereNumber(['customer', 'document'])
            ->name('documents.destroy');

        Route::get('credit-profile', [CustomerRelationController::class, 'creditProfile'])
            ->whereNumber('customer')
            ->name('credit-profile.show');
        Route::put('credit-profile', [CustomerRelationController::class, 'updateCreditProfile'])
            ->whereNumber('customer')
            ->name('credit-profile.update');
        Route::get('status-history', [CustomerRelationController::class, 'statusHistory'])
            ->whereNumber('customer')
            ->name('status-history.index');
    });

    Route::apiResource('customers', CustomerController::class);

    Route::get('customer-categories/lookup', [CustomerCategoryController::class, 'lookup'])
        ->name('customer-categories.lookup');
    Route::apiResource('customer-categories', CustomerCategoryController::class);
});
