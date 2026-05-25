<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Purchase\Presentation\Http\Controllers\PurchaseOrderController;
use Modules\Purchase\Presentation\Http\Controllers\PurchaseOrderLineController;
use Modules\Purchase\Presentation\Http\Controllers\GrnHeaderController;
use Modules\Purchase\Presentation\Http\Controllers\GrnLineController;
use Modules\Purchase\Presentation\Http\Controllers\PurchaseReturnController;
use Modules\Purchase\Presentation\Http\Controllers\PurchaseReturnLineController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);

Route::prefix('api/purchase')
    ->middleware([
        'api',
        'auth:' . $protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
    ])
    ->name('purchase.')
    ->group(function (): void {
        Route::apiResource('purchase-orders', PurchaseOrderController::class);
        Route::apiResource('purchase-order-lines', PurchaseOrderLineController::class);
        Route::apiResource('grn-headers', GrnHeaderController::class);
        Route::apiResource('grn-lines', GrnLineController::class);
        Route::apiResource('purchase-returns', PurchaseReturnController::class);
        Route::apiResource('purchase-return-lines', PurchaseReturnLineController::class);
    });