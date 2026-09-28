<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Selling\Http\Controllers\SaleController;
use Modules\Selling\Services\SellingAuthorizationService;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit').':required',
    'tenant.feature:selling',
];
$permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');
$requires = static fn (string $permission): string => $permissionMiddleware.':'.$permission;

Route::prefix('api/v1/selling')->middleware($middleware)->name('api.v1.selling.')->group(function () use ($requires): void {
    Route::get('items/lookup', [SaleController::class, 'itemLookup'])->middleware([
        'tenant.feature:item',
        $requires(SellingAuthorizationService::SALES_CREATE),
    ])->name('items.lookup');
    Route::get('sales', [SaleController::class, 'index'])->middleware($requires(SellingAuthorizationService::SALES_VIEW))->name('sales.index');
    Route::post('sales', [SaleController::class, 'store'])->middleware($requires(SellingAuthorizationService::SALES_CREATE))->name('sales.store');
    Route::get('sales/{sale}', [SaleController::class, 'show'])->whereNumber('sale')->middleware([
        $requires(SellingAuthorizationService::SALES_VIEW),
        $requires(SellingAuthorizationService::RETURNS_VIEW),
    ])->name('sales.show');
    Route::post('sales/{sale}/returns', [SaleController::class, 'storeReturn'])->whereNumber('sale')->middleware($requires(SellingAuthorizationService::RETURNS_CREATE))->name('sales.returns.store');
});
