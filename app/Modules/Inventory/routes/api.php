<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Presentation\Http\Controllers\InventoryRecalculationController;
use Modules\Inventory\Presentation\Http\Controllers\InventoryResourceController;

Route::prefix('api/inventory')
    ->middleware('api')
    ->name('inventory.')
    ->group(function (): void {
        Route::prefix('tenants/{tenant}')
            ->name('tenants.')
            ->group(function (): void {
                Route::post('stock-levels/{stockLevel}/recalculate', [InventoryRecalculationController::class, 'stockLevel'])->name('stock-levels.recalculate');

                Route::get('{resource}', [InventoryResourceController::class, 'index'])->name('resources.index');
                Route::post('{resource}', [InventoryResourceController::class, 'store'])->name('resources.store');
                Route::get('{resource}/{id}', [InventoryResourceController::class, 'show'])->name('resources.show');
                Route::put('{resource}/{id}', [InventoryResourceController::class, 'update'])->name('resources.update');
                Route::patch('{resource}/{id}', [InventoryResourceController::class, 'update'])->name('resources.patch');
                Route::delete('{resource}/{id}', [InventoryResourceController::class, 'destroy'])->name('resources.destroy');
            });
    });
