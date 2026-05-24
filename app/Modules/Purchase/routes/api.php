<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Purchase\Presentation\Http\Controllers\PurchaseRecalculationController;
use Modules\Purchase\Presentation\Http\Controllers\PurchaseResourceController;

Route::prefix('api/purchase')
    ->middleware('api')
    ->name('purchase.')
    ->group(function (): void {
        Route::prefix('tenants/{tenant}')
            ->name('tenants.')
            ->group(function (): void {
                Route::post('purchase-orders/{purchaseOrder}/recalculate', [PurchaseRecalculationController::class, 'purchaseOrder'])->name('purchase-orders.recalculate');
                Route::post('grn-headers/{grnHeader}/recalculate', [PurchaseRecalculationController::class, 'grnHeader'])->name('grn-headers.recalculate');
                Route::post('purchase-returns/{purchaseReturn}/recalculate', [PurchaseRecalculationController::class, 'purchaseReturn'])->name('purchase-returns.recalculate');

                Route::get('{resource}', [PurchaseResourceController::class, 'index'])->name('resources.index');
                Route::post('{resource}', [PurchaseResourceController::class, 'store'])->name('resources.store');
                Route::get('{resource}/{id}', [PurchaseResourceController::class, 'show'])->name('resources.show');
                Route::put('{resource}/{id}', [PurchaseResourceController::class, 'update'])->name('resources.update');
                Route::patch('{resource}/{id}', [PurchaseResourceController::class, 'update'])->name('resources.patch');
                Route::delete('{resource}/{id}', [PurchaseResourceController::class, 'destroy'])->name('resources.destroy');
            });
    });
