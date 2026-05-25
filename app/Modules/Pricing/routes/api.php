<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Pricing\Presentation\Http\Controllers\PriceListController;
use Modules\Pricing\Presentation\Http\Controllers\PriceListItemController;
use Modules\Pricing\Presentation\Http\Controllers\SupplierPriceListController;
use Modules\Pricing\Presentation\Http\Controllers\CustomerPriceListController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);

Route::prefix('api/pricing')
    ->middleware([
        'api',
        'auth:' . $protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
    ])
    ->name('pricing.')
    ->group(function (): void {
        Route::apiResource('price-lists', PriceListController::class);
        Route::apiResource('price-list-items', PriceListItemController::class);
        Route::apiResource('supplier-price-lists', SupplierPriceListController::class);
        Route::apiResource('customer-price-lists', CustomerPriceListController::class);
    });