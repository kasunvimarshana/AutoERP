<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Customer\Http\Controllers\CustomerCategoryController;
use Modules\Customer\Http\Controllers\CustomerController;
use Modules\Customer\Http\Controllers\CustomerRelationController;
use Modules\Customer\Services\CustomerAuthorizationService;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit').':required',
    'tenant.feature:customer',
];
$permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');
$requires = static fn (string $permission): string => $permissionMiddleware.':'.$permission;

Route::prefix('api/v1')->middleware($middleware)->name('api.v1.')->group(function () use ($requires): void {
    Route::get('customers/lookup/{kind?}', [CustomerController::class, 'lookup'])
        ->whereIn('kind', ['active', 'credit-allowed'])
        ->middleware($requires(CustomerAuthorizationService::VIEW))
        ->name('customers.lookup');
    Route::post('customers/code-reservations', [CustomerController::class, 'generateCode'])
        ->middleware($requires(CustomerAuthorizationService::CREATE))
        ->name('customers.code-reservations.store');
    Route::post('customers/with-relations', [CustomerController::class, 'storeWithRelations'])
        ->middleware($requires(CustomerAuthorizationService::CREATE))
        ->name('customers.with-relations.store');
    Route::patch('customers/{customer}/activate', [CustomerController::class, 'activate'])
        ->whereNumber('customer')
        ->middleware($requires(CustomerAuthorizationService::UPDATE))
        ->name('customers.activate');
    Route::patch('customers/{customer}/deactivate', [CustomerController::class, 'deactivate'])
        ->whereNumber('customer')
        ->middleware($requires(CustomerAuthorizationService::UPDATE))
        ->name('customers.deactivate');
    Route::patch('customers/{customer}/status', [CustomerController::class, 'changeStatus'])
        ->whereNumber('customer')
        ->middleware($requires(CustomerAuthorizationService::UPDATE))
        ->name('customers.status');

    Route::prefix('customers/{customer}')->name('customers.')->group(function () use ($requires): void {
        Route::get('contacts', [CustomerRelationController::class, 'contacts'])
            ->whereNumber('customer')
            ->middleware($requires(CustomerAuthorizationService::VIEW))
            ->name('contacts.index');
        Route::post('contacts', [CustomerRelationController::class, 'storeContact'])
            ->whereNumber('customer')
            ->middleware($requires(CustomerAuthorizationService::UPDATE))
            ->name('contacts.store');
        Route::put('contacts/{contact}', [CustomerRelationController::class, 'updateContact'])
            ->whereNumber(['customer', 'contact'])
            ->middleware($requires(CustomerAuthorizationService::UPDATE))
            ->name('contacts.update');
        Route::delete('contacts/{contact}', [CustomerRelationController::class, 'deleteContact'])
            ->whereNumber(['customer', 'contact'])
            ->middleware($requires(CustomerAuthorizationService::UPDATE))
            ->name('contacts.destroy');

        Route::get('addresses', [CustomerRelationController::class, 'addresses'])
            ->whereNumber('customer')
            ->middleware($requires(CustomerAuthorizationService::VIEW))
            ->name('addresses.index');
        Route::post('addresses', [CustomerRelationController::class, 'storeAddress'])
            ->whereNumber('customer')
            ->middleware($requires(CustomerAuthorizationService::UPDATE))
            ->name('addresses.store');
        Route::put('addresses/{address}', [CustomerRelationController::class, 'updateAddress'])
            ->whereNumber(['customer', 'address'])
            ->middleware($requires(CustomerAuthorizationService::UPDATE))
            ->name('addresses.update');
        Route::delete('addresses/{address}', [CustomerRelationController::class, 'deleteAddress'])
            ->whereNumber(['customer', 'address'])
            ->middleware($requires(CustomerAuthorizationService::UPDATE))
            ->name('addresses.destroy');

        Route::get('bank-accounts', [CustomerRelationController::class, 'bankAccounts'])
            ->whereNumber('customer')
            ->middleware($requires(CustomerAuthorizationService::VIEW))
            ->name('bank-accounts.index');
        Route::post('bank-accounts', [CustomerRelationController::class, 'storeBankAccount'])
            ->whereNumber('customer')
            ->middleware($requires(CustomerAuthorizationService::UPDATE))
            ->name('bank-accounts.store');
        Route::put('bank-accounts/{bankAccount}', [CustomerRelationController::class, 'updateBankAccount'])
            ->whereNumber(['customer', 'bankAccount'])
            ->middleware($requires(CustomerAuthorizationService::UPDATE))
            ->name('bank-accounts.update');
        Route::delete('bank-accounts/{bankAccount}', [CustomerRelationController::class, 'deleteBankAccount'])
            ->whereNumber(['customer', 'bankAccount'])
            ->middleware($requires(CustomerAuthorizationService::UPDATE))
            ->name('bank-accounts.destroy');

        Route::get('categories', [CustomerRelationController::class, 'categories'])
            ->whereNumber('customer')
            ->middleware($requires(CustomerAuthorizationService::VIEW))
            ->name('categories.index');
        Route::post('categories', [CustomerRelationController::class, 'assignCategory'])
            ->whereNumber('customer')
            ->middleware($requires(CustomerAuthorizationService::UPDATE))
            ->name('categories.store');
        Route::delete('categories/{category}', [CustomerRelationController::class, 'deleteCategory'])
            ->whereNumber(['customer', 'category'])
            ->middleware($requires(CustomerAuthorizationService::UPDATE))
            ->name('categories.destroy');

        Route::get('documents', [CustomerRelationController::class, 'documents'])
            ->whereNumber('customer')
            ->middleware($requires(CustomerAuthorizationService::VIEW))
            ->name('documents.index');
        Route::post('documents', [CustomerRelationController::class, 'storeDocument'])
            ->whereNumber('customer')
            ->middleware($requires(CustomerAuthorizationService::UPDATE))
            ->name('documents.store');
        Route::put('documents/{document}', [CustomerRelationController::class, 'updateDocument'])
            ->whereNumber(['customer', 'document'])
            ->middleware($requires(CustomerAuthorizationService::UPDATE))
            ->name('documents.update');
        Route::delete('documents/{document}', [CustomerRelationController::class, 'deleteDocument'])
            ->whereNumber(['customer', 'document'])
            ->middleware($requires(CustomerAuthorizationService::UPDATE))
            ->name('documents.destroy');

        Route::get('credit-profile', [CustomerRelationController::class, 'creditProfile'])
            ->whereNumber('customer')
            ->middleware($requires(CustomerAuthorizationService::VIEW))
            ->name('credit-profile.show');
        Route::put('credit-profile', [CustomerRelationController::class, 'updateCreditProfile'])
            ->whereNumber('customer')
            ->middleware($requires(CustomerAuthorizationService::UPDATE))
            ->name('credit-profile.update');
        Route::get('status-history', [CustomerRelationController::class, 'statusHistory'])
            ->whereNumber('customer')
            ->middleware($requires(CustomerAuthorizationService::VIEW))
            ->name('status-history.index');
    });

    Route::get('customers', [CustomerController::class, 'index'])
        ->middleware($requires(CustomerAuthorizationService::VIEW))
        ->name('customers.index');
    Route::post('customers', [CustomerController::class, 'store'])
        ->middleware($requires(CustomerAuthorizationService::CREATE))
        ->name('customers.store');
    Route::get('customers/{customer}', [CustomerController::class, 'show'])
        ->whereNumber('customer')
        ->middleware($requires(CustomerAuthorizationService::VIEW))
        ->name('customers.show');
    Route::match(['put', 'patch'], 'customers/{customer}', [CustomerController::class, 'update'])
        ->whereNumber('customer')
        ->middleware($requires(CustomerAuthorizationService::UPDATE))
        ->name('customers.update');
    Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])
        ->whereNumber('customer')
        ->middleware($requires(CustomerAuthorizationService::DELETE))
        ->name('customers.destroy');

    Route::get('customer-categories/lookup', [CustomerCategoryController::class, 'lookup'])
        ->middleware($requires(CustomerAuthorizationService::VIEW))
        ->name('customer-categories.lookup');
    Route::get('customer-categories', [CustomerCategoryController::class, 'index'])
        ->middleware($requires(CustomerAuthorizationService::VIEW))
        ->name('customer-categories.index');
    Route::post('customer-categories', [CustomerCategoryController::class, 'store'])
        ->middleware($requires(CustomerAuthorizationService::UPDATE))
        ->name('customer-categories.store');
    Route::get('customer-categories/{customer_category}', [CustomerCategoryController::class, 'show'])
        ->whereNumber('customer_category')
        ->middleware($requires(CustomerAuthorizationService::VIEW))
        ->name('customer-categories.show');
    Route::match(['put', 'patch'], 'customer-categories/{customer_category}', [CustomerCategoryController::class, 'update'])
        ->whereNumber('customer_category')
        ->middleware($requires(CustomerAuthorizationService::UPDATE))
        ->name('customer-categories.update');
    Route::delete('customer-categories/{customer_category}', [CustomerCategoryController::class, 'destroy'])
        ->whereNumber('customer_category')
        ->middleware($requires(CustomerAuthorizationService::UPDATE))
        ->name('customer-categories.destroy');
});
