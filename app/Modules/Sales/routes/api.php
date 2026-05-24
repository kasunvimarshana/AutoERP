<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Sales\Presentation\Http\Controllers\SalesRecalculationController;
use Modules\Sales\Presentation\Http\Controllers\SalesResourceController;

Route::prefix('api/sales')
    ->middleware('api')
    ->name('sales.')
    ->group(function (): void {
        Route::prefix('tenants/{tenant}')
            ->name('tenants.')
            ->group(function (): void {
                Route::post('sales-orders/{salesOrder}/recalculate', [SalesRecalculationController::class, 'salesOrder'])->name('sales-orders.recalculate');
                Route::post('gdn-headers/{gdnHeader}/recalculate', [SalesRecalculationController::class, 'gdnHeader'])->name('gdn-headers.recalculate');
                Route::post('sales-returns/{salesReturn}/recalculate', [SalesRecalculationController::class, 'salesReturn'])->name('sales-returns.recalculate');

                Route::get('{resource}', [SalesResourceController::class, 'index'])->name('resources.index');
                Route::post('{resource}', [SalesResourceController::class, 'store'])->name('resources.store');
                Route::get('{resource}/{id}', [SalesResourceController::class, 'show'])->name('resources.show');
                Route::put('{resource}/{id}', [SalesResourceController::class, 'update'])->name('resources.update');
                Route::patch('{resource}/{id}', [SalesResourceController::class, 'update'])->name('resources.patch');
                Route::delete('{resource}/{id}', [SalesResourceController::class, 'destroy'])->name('resources.destroy');
            });
    });
