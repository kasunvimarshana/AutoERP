<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Supplier\Http\Controllers\SupplierCategoryController;
use Modules\Supplier\Http\Controllers\SupplierController;
use Modules\Supplier\Http\Controllers\SupplierRelationController;
use Modules\Supplier\Services\SupplierAuthorizationService;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit').':required',
    'tenant.feature:supplier',
];
$permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');
$requires = static fn (string $permission): string => $permissionMiddleware.':'.$permission;

Route::prefix('api/v1')->middleware($middleware)->name('api.v1.')->group(function () use ($requires): void {
    Route::get('suppliers/lookup/{kind?}', [SupplierController::class, 'lookup'])
        ->whereIn('kind', ['active', 'credit-allowed', 'by-item'])
        ->middleware($requires(SupplierAuthorizationService::VIEW))
        ->name('suppliers.lookup');
    Route::post('suppliers/with-relations', [SupplierController::class, 'storeWithRelations'])
        ->middleware($requires(SupplierAuthorizationService::CREATE))
        ->name('suppliers.with-relations.store');
    Route::patch('suppliers/{supplier}/activate', [SupplierController::class, 'activate'])
        ->whereNumber('supplier')
        ->middleware($requires(SupplierAuthorizationService::UPDATE))
        ->name('suppliers.activate');
    Route::patch('suppliers/{supplier}/deactivate', [SupplierController::class, 'deactivate'])
        ->whereNumber('supplier')
        ->middleware($requires(SupplierAuthorizationService::UPDATE))
        ->name('suppliers.deactivate');
    Route::patch('suppliers/{supplier}/status', [SupplierController::class, 'changeStatus'])
        ->whereNumber('supplier')
        ->middleware($requires(SupplierAuthorizationService::UPDATE))
        ->name('suppliers.status');

    Route::prefix('suppliers/{supplier}')->name('suppliers.')->group(function () use ($requires): void {
        Route::get('contacts', [SupplierRelationController::class, 'contacts'])
            ->whereNumber('supplier')
            ->middleware($requires(SupplierAuthorizationService::VIEW))
            ->name('contacts.index');
        Route::post('contacts', [SupplierRelationController::class, 'storeContact'])
            ->whereNumber('supplier')
            ->middleware($requires(SupplierAuthorizationService::UPDATE))
            ->name('contacts.store');
        Route::put('contacts/{contact}', [SupplierRelationController::class, 'updateContact'])
            ->whereNumber(['supplier', 'contact'])
            ->middleware($requires(SupplierAuthorizationService::UPDATE))
            ->name('contacts.update');
        Route::delete('contacts/{contact}', [SupplierRelationController::class, 'deleteContact'])
            ->whereNumber(['supplier', 'contact'])
            ->middleware($requires(SupplierAuthorizationService::UPDATE))
            ->name('contacts.destroy');

        Route::get('addresses', [SupplierRelationController::class, 'addresses'])
            ->whereNumber('supplier')
            ->middleware($requires(SupplierAuthorizationService::VIEW))
            ->name('addresses.index');
        Route::post('addresses', [SupplierRelationController::class, 'storeAddress'])
            ->whereNumber('supplier')
            ->middleware($requires(SupplierAuthorizationService::UPDATE))
            ->name('addresses.store');
        Route::put('addresses/{address}', [SupplierRelationController::class, 'updateAddress'])
            ->whereNumber(['supplier', 'address'])
            ->middleware($requires(SupplierAuthorizationService::UPDATE))
            ->name('addresses.update');
        Route::delete('addresses/{address}', [SupplierRelationController::class, 'deleteAddress'])
            ->whereNumber(['supplier', 'address'])
            ->middleware($requires(SupplierAuthorizationService::UPDATE))
            ->name('addresses.destroy');

        Route::get('bank-accounts', [SupplierRelationController::class, 'bankAccounts'])
            ->whereNumber('supplier')
            ->middleware($requires(SupplierAuthorizationService::VIEW))
            ->name('bank-accounts.index');
        Route::post('bank-accounts', [SupplierRelationController::class, 'storeBankAccount'])
            ->whereNumber('supplier')
            ->middleware($requires(SupplierAuthorizationService::UPDATE))
            ->name('bank-accounts.store');
        Route::put('bank-accounts/{bankAccount}', [SupplierRelationController::class, 'updateBankAccount'])
            ->whereNumber(['supplier', 'bankAccount'])
            ->middleware($requires(SupplierAuthorizationService::UPDATE))
            ->name('bank-accounts.update');
        Route::delete('bank-accounts/{bankAccount}', [SupplierRelationController::class, 'deleteBankAccount'])
            ->whereNumber(['supplier', 'bankAccount'])
            ->middleware($requires(SupplierAuthorizationService::UPDATE))
            ->name('bank-accounts.destroy');

        Route::get('categories', [SupplierRelationController::class, 'categories'])
            ->whereNumber('supplier')
            ->middleware($requires(SupplierAuthorizationService::VIEW))
            ->name('categories.index');
        Route::post('categories', [SupplierRelationController::class, 'assignCategory'])
            ->whereNumber('supplier')
            ->middleware($requires(SupplierAuthorizationService::UPDATE))
            ->name('categories.store');
        Route::delete('categories/{category}', [SupplierRelationController::class, 'deleteCategory'])
            ->whereNumber(['supplier', 'category'])
            ->middleware($requires(SupplierAuthorizationService::UPDATE))
            ->name('categories.destroy');

        Route::get('documents', [SupplierRelationController::class, 'documents'])
            ->whereNumber('supplier')
            ->middleware($requires(SupplierAuthorizationService::VIEW))
            ->name('documents.index');
        Route::post('documents', [SupplierRelationController::class, 'storeDocument'])
            ->whereNumber('supplier')
            ->middleware($requires(SupplierAuthorizationService::UPDATE))
            ->name('documents.store');
        Route::put('documents/{document}', [SupplierRelationController::class, 'updateDocument'])
            ->whereNumber(['supplier', 'document'])
            ->middleware($requires(SupplierAuthorizationService::UPDATE))
            ->name('documents.update');
        Route::delete('documents/{document}', [SupplierRelationController::class, 'deleteDocument'])
            ->whereNumber(['supplier', 'document'])
            ->middleware($requires(SupplierAuthorizationService::UPDATE))
            ->name('documents.destroy');

        Route::get('item-mappings', [SupplierRelationController::class, 'itemMappings'])
            ->whereNumber('supplier')
            ->middleware($requires(SupplierAuthorizationService::VIEW))
            ->name('item-mappings.index');
        Route::post('item-mappings', [SupplierRelationController::class, 'storeItemMapping'])
            ->whereNumber('supplier')
            ->middleware($requires(SupplierAuthorizationService::UPDATE))
            ->name('item-mappings.store');
        Route::put('item-mappings/{mapping}', [SupplierRelationController::class, 'updateItemMapping'])
            ->whereNumber(['supplier', 'mapping'])
            ->middleware($requires(SupplierAuthorizationService::UPDATE))
            ->name('item-mappings.update');
        Route::delete('item-mappings/{mapping}', [SupplierRelationController::class, 'deleteItemMapping'])
            ->whereNumber(['supplier', 'mapping'])
            ->middleware($requires(SupplierAuthorizationService::UPDATE))
            ->name('item-mappings.destroy');

        Route::get('credit-profile', [SupplierRelationController::class, 'creditProfile'])
            ->whereNumber('supplier')
            ->middleware($requires(SupplierAuthorizationService::VIEW))
            ->name('credit-profile.show');
        Route::put('credit-profile', [SupplierRelationController::class, 'updateCreditProfile'])
            ->whereNumber('supplier')
            ->middleware($requires(SupplierAuthorizationService::UPDATE))
            ->name('credit-profile.update');
        Route::get('status-history', [SupplierRelationController::class, 'statusHistory'])
            ->whereNumber('supplier')
            ->middleware($requires(SupplierAuthorizationService::VIEW))
            ->name('status-history.index');
    });

    Route::get('suppliers', [SupplierController::class, 'index'])
        ->middleware($requires(SupplierAuthorizationService::VIEW))
        ->name('suppliers.index');
    Route::post('suppliers', [SupplierController::class, 'store'])
        ->middleware($requires(SupplierAuthorizationService::CREATE))
        ->name('suppliers.store');
    Route::get('suppliers/{supplier}', [SupplierController::class, 'show'])
        ->whereNumber('supplier')
        ->middleware($requires(SupplierAuthorizationService::VIEW))
        ->name('suppliers.show');
    Route::match(['put', 'patch'], 'suppliers/{supplier}', [SupplierController::class, 'update'])
        ->whereNumber('supplier')
        ->middleware($requires(SupplierAuthorizationService::UPDATE))
        ->name('suppliers.update');
    Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])
        ->whereNumber('supplier')
        ->middleware($requires(SupplierAuthorizationService::DELETE))
        ->name('suppliers.destroy');

    Route::get('supplier-categories/lookup', [SupplierCategoryController::class, 'lookup'])
        ->middleware($requires(SupplierAuthorizationService::VIEW))
        ->name('supplier-categories.lookup');
    Route::get('supplier-categories', [SupplierCategoryController::class, 'index'])
        ->middleware($requires(SupplierAuthorizationService::VIEW))
        ->name('supplier-categories.index');
    Route::post('supplier-categories', [SupplierCategoryController::class, 'store'])
        ->middleware($requires(SupplierAuthorizationService::UPDATE))
        ->name('supplier-categories.store');
    Route::get('supplier-categories/{supplier_category}', [SupplierCategoryController::class, 'show'])
        ->whereNumber('supplier_category')
        ->middleware($requires(SupplierAuthorizationService::VIEW))
        ->name('supplier-categories.show');
    Route::match(['put', 'patch'], 'supplier-categories/{supplier_category}', [SupplierCategoryController::class, 'update'])
        ->whereNumber('supplier_category')
        ->middleware($requires(SupplierAuthorizationService::UPDATE))
        ->name('supplier-categories.update');
    Route::delete('supplier-categories/{supplier_category}', [SupplierCategoryController::class, 'destroy'])
        ->whereNumber('supplier_category')
        ->middleware($requires(SupplierAuthorizationService::UPDATE))
        ->name('supplier-categories.destroy');
});
