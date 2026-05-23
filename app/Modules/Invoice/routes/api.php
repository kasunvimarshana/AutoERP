<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Invoice\Presentation\Http\Controllers\InvoiceRecalculationController;
use Modules\Invoice\Presentation\Http\Controllers\InvoiceResourceController;

Route::prefix('api/invoice')
    ->middleware('api')
    ->name('invoice.')
    ->group(function (): void {
        Route::prefix('tenants/{tenant}')
            ->name('tenants.')
            ->group(function (): void {
                Route::post('invoices/{invoice}/recalculate', [InvoiceRecalculationController::class, 'invoice'])->name('invoices.recalculate');
                Route::post('references/{reference}/recalculate', [InvoiceRecalculationController::class, 'reference'])->name('references.recalculate');

                Route::get('{resource}', [InvoiceResourceController::class, 'index'])->name('resources.index');
                Route::post('{resource}', [InvoiceResourceController::class, 'store'])->name('resources.store');
                Route::get('{resource}/{id}', [InvoiceResourceController::class, 'show'])->name('resources.show');
                Route::put('{resource}/{id}', [InvoiceResourceController::class, 'update'])->name('resources.update');
                Route::patch('{resource}/{id}', [InvoiceResourceController::class, 'update'])->name('resources.patch');
                Route::delete('{resource}/{id}', [InvoiceResourceController::class, 'destroy'])->name('resources.destroy');
            });
    });
