<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Purchase\Presentation\Http\Controllers\PurchaseOrderController;
use Modules\Purchase\Presentation\Http\Controllers\PurchaseOrderLineController;
use Modules\Purchase\Presentation\Http\Controllers\GrnHeaderController;
use Modules\Purchase\Presentation\Http\Controllers\GrnLineController;
use Modules\Purchase\Presentation\Http\Controllers\PurchaseAdvancePaymentController;
use Modules\Purchase\Presentation\Http\Controllers\PurchaseInvoiceController;
use Modules\Purchase\Presentation\Http\Controllers\PurchaseReturnController;
use Modules\Purchase\Presentation\Http\Controllers\PurchaseReturnLineController;
use Modules\Purchase\Presentation\Http\Controllers\PurchasePaymentController;

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
        Route::patch('purchase-orders/{purchase_order}/confirm', [PurchaseOrderController::class, 'confirm'])
            ->name('purchase-orders.confirm');
        Route::post('purchase-orders/{purchase_order}/confirm', [PurchaseOrderController::class, 'confirm'])
            ->name('purchase-orders.confirm.post');
        Route::patch('purchase-orders/{purchase_order}/cancel', [PurchaseOrderController::class, 'cancel'])
            ->name('purchase-orders.cancel');
        Route::post('purchase-orders/{purchase_order}/cancel', [PurchaseOrderController::class, 'cancel'])
            ->name('purchase-orders.cancel.post');
        Route::apiResource('purchase-orders', PurchaseOrderController::class);
        Route::apiResource('purchase-order-lines', PurchaseOrderLineController::class);
        Route::patch('grn-headers/{grn_header}/confirm', [GrnHeaderController::class, 'confirm'])
            ->name('grn-headers.confirm');
        Route::post('grn-headers/{grn_header}/confirm', [GrnHeaderController::class, 'confirm'])
            ->name('grn-headers.confirm.post');
        Route::apiResource('grn-headers', GrnHeaderController::class);
        Route::apiResource('grn-lines', GrnLineController::class);
        Route::patch('purchase-returns/{purchase_return}/approve', [PurchaseReturnController::class, 'approve'])
            ->name('purchase-returns.approve');
        Route::post('purchase-returns/{purchase_return}/approve', [PurchaseReturnController::class, 'approve'])
            ->name('purchase-returns.approve.post');
        Route::apiResource('purchase-returns', PurchaseReturnController::class);
        Route::apiResource('purchase-return-lines', PurchaseReturnLineController::class);
        Route::post('purchase-invoices', [PurchaseInvoiceController::class, 'store'])
            ->name('purchase-invoices.store');
        Route::patch('purchase-invoices/{invoice}/approve', [PurchaseInvoiceController::class, 'approve'])
            ->name('purchase-invoices.approve');
        Route::post('purchase-invoices/{invoice}/approve', [PurchaseInvoiceController::class, 'approve'])
            ->name('purchase-invoices.approve.post');
        Route::post('purchase-payments', [PurchasePaymentController::class, 'store'])
            ->name('purchase-payments.store');
        Route::post('advance-payments', [PurchaseAdvancePaymentController::class, 'store'])
            ->name('advance-payments.store');
        Route::post('advance-payments/{advance_payment}/allocate', [PurchaseAdvancePaymentController::class, 'allocate'])
            ->name('advance-payments.allocate');
    });
