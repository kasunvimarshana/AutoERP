<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Supplier\Http\Controllers\SupplierController;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit'),
];

Route::prefix('api/v1')->middleware($middleware)->name('api.v1.')->group(function (): void {
    Route::get('suppliers/lookup', [SupplierController::class, 'lookup'])->name('suppliers.lookup');
    Route::get('suppliers/categories/lookup', [SupplierController::class, 'categories'])->name('suppliers.categories.lookup');
    Route::get('suppliers/item-mappings/lookup', [SupplierController::class, 'itemMappings'])->name('suppliers.item-mappings.lookup');
    Route::patch('suppliers/{supplier}/status', [SupplierController::class, 'changeStatus'])->whereNumber('supplier')->name('suppliers.status');
    Route::apiResource('suppliers', SupplierController::class)->only(['index', 'store', 'show', 'update']);
});
