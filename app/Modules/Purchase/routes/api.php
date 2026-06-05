<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Purchase\Presentation\Http\Controllers\PurchaseController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit');

Route::prefix('api/purchase')
    ->middleware(['api', 'auth:'.$protectedGuard, $currentUserMiddleware, $currentTenantMiddleware, $currentOrganizationUnitMiddleware])
    ->name('purchase.')
    ->group(function (): void {
        Route::get('dashboard', [PurchaseController::class, 'dashboard'])->name('dashboard');
        Route::get('lookups/{type}', [PurchaseController::class, 'lookup'])->name('lookups');

        Route::get('purchase-orders', [PurchaseController::class, 'orders'])->name('purchase-orders.index');
        Route::post('purchase-orders', [PurchaseController::class, 'storeOrder'])->name('purchase-orders.store');
        Route::get('purchase-orders/{purchaseOrder}', [PurchaseController::class, 'showOrder'])->name('purchase-orders.show');
        Route::match(['put', 'patch'], 'purchase-orders/{purchaseOrder}', [PurchaseController::class, 'updateOrder'])->name('purchase-orders.update');
        Route::delete('purchase-orders/{purchaseOrder}', [PurchaseController::class, 'destroyOrder'])->name('purchase-orders.destroy');
        Route::post('purchase-orders/{purchaseOrder}/confirm', [PurchaseController::class, 'confirmOrder'])->name('purchase-orders.confirm');
        Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseController::class, 'cancelOrder'])->name('purchase-orders.cancel');
        Route::post('purchase-orders/{purchaseOrder}/close', [PurchaseController::class, 'closeOrder'])->name('purchase-orders.close');
        Route::post('purchase-orders/{purchaseOrder}/invoice', [PurchaseController::class, 'invoiceOrder'])->name('purchase-orders.invoice');

        Route::get('grns', [PurchaseController::class, 'grns'])->name('grns.index');
        Route::post('grns', [PurchaseController::class, 'storeGrn'])->name('grns.store');
        Route::get('grns/{grn}', [PurchaseController::class, 'showGrn'])->name('grns.show');
        Route::match(['put', 'patch'], 'grns/{grn}', [PurchaseController::class, 'updateGrn'])->name('grns.update');
        Route::delete('grns/{grn}', [PurchaseController::class, 'destroyGrn'])->name('grns.destroy');
        Route::post('grns/{grn}/post', [PurchaseController::class, 'postGrn'])->name('grns.post');
        Route::post('grns/{grn}/invoice', [PurchaseController::class, 'invoiceGrn'])->name('grns.invoice');

        Route::get('purchase-returns', [PurchaseController::class, 'returns'])->name('purchase-returns.index');
        Route::post('purchase-returns', [PurchaseController::class, 'storeReturn'])->name('purchase-returns.store');
        Route::get('purchase-returns/{purchaseReturn}', [PurchaseController::class, 'showReturn'])->name('purchase-returns.show');
        Route::match(['put', 'patch'], 'purchase-returns/{purchaseReturn}', [PurchaseController::class, 'updateReturn'])->name('purchase-returns.update');
        Route::delete('purchase-returns/{purchaseReturn}', [PurchaseController::class, 'destroyReturn'])->name('purchase-returns.destroy');
        Route::post('purchase-returns/{purchaseReturn}/post', [PurchaseController::class, 'postReturn'])->name('purchase-returns.post');
    });
