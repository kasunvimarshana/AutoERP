<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Purchase\Presentation\Http\Controllers\PurchaseOrderController;
use Modules\Purchase\Presentation\Http\Controllers\PurchaseOrderLineController;
use Modules\Purchase\Presentation\Http\Controllers\GrnHeaderController;
use Modules\Purchase\Presentation\Http\Controllers\GrnLineController;
use Modules\Purchase\Presentation\Http\Controllers\PurchaseReturnController;
use Modules\Purchase\Presentation\Http\Controllers\PurchaseReturnLineController;
use Modules\Purchase\Presentation\Http\Controllers\PurchaseWorkflowController;

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

        Route::prefix('workflows/{entityType}/{id}')
            ->whereIn('entityType', ['purchase_order', 'grn_header', 'purchase_return'])
            ->group(function (): void {
                Route::post('transition', [PurchaseWorkflowController::class, 'transition'])
                    ->name('workflows.transition');
                Route::post('document', [PurchaseWorkflowController::class, 'createDocument'])
                    ->name('workflows.document');
                Route::post('payment/allocate', [PurchaseWorkflowController::class, 'allocatePayment'])
                    ->name('workflows.payment.allocate');
                Route::post('inventory/post', [PurchaseWorkflowController::class, 'postInventory'])
                    ->name('workflows.inventory.post');
                Route::post('finance/post', [PurchaseWorkflowController::class, 'postFinance'])
                    ->name('workflows.finance.post');
                Route::post('finance/reverse', [PurchaseWorkflowController::class, 'reverseFinance'])
                    ->name('workflows.finance.reverse');
            });
    });
