<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Item\Http\Controllers\ItemController;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit'),
];

Route::prefix('api/v1')->middleware($middleware)->name('api.v1.')->group(function (): void {
    Route::get('items/lookup/{kind?}', [ItemController::class, 'lookup'])->name('items.lookup');
    Route::get('items/categories/lookup', [ItemController::class, 'categories'])->name('items.categories.lookup');
    Route::get('items/brands/lookup', [ItemController::class, 'brands'])->name('items.brands.lookup');
    Route::apiResource('items', ItemController::class)->only(['index', 'store', 'show', 'update']);
});
