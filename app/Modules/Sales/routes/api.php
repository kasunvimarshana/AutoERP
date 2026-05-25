<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Sales\Presentation\Http\Controllers\SalesOrderController;
use Modules\Sales\Presentation\Http\Controllers\SalesOrderLineController;
use Modules\Sales\Presentation\Http\Controllers\GdnHeaderController;
use Modules\Sales\Presentation\Http\Controllers\GdnLineController;
use Modules\Sales\Presentation\Http\Controllers\SalesReturnController;
use Modules\Sales\Presentation\Http\Controllers\SalesReturnLineController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);

Route::prefix('api/sales')
    ->middleware([
        'api',
        'auth:' . $protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
    ])
    ->name('sales.')
    ->group(function (): void {
        Route::apiResource('sales-orders', SalesOrderController::class);
        Route::apiResource('sales-order-lines', SalesOrderLineController::class);
        Route::apiResource('gdn-headers', GdnHeaderController::class);
        Route::apiResource('gdn-lines', GdnLineController::class);
        Route::apiResource('sales-returns', SalesReturnController::class);
        Route::apiResource('sales-return-lines', SalesReturnLineController::class);
    });