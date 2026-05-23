<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Item\Presentation\Http\Controllers\ItemResourceController;

Route::prefix('api/item')
    ->middleware('api')
    ->name('item.')
    ->group(function (): void {
        Route::prefix('tenants/{tenant}')
            ->name('tenants.')
            ->group(function (): void {
                Route::get('{resource}', [ItemResourceController::class, 'index'])->name('resources.index');
                Route::post('{resource}', [ItemResourceController::class, 'store'])->name('resources.store');
                Route::get('{resource}/{id}', [ItemResourceController::class, 'show'])->name('resources.show');
                Route::put('{resource}/{id}', [ItemResourceController::class, 'update'])->name('resources.update');
                Route::patch('{resource}/{id}', [ItemResourceController::class, 'update'])->name('resources.patch');
                Route::delete('{resource}/{id}', [ItemResourceController::class, 'destroy'])->name('resources.destroy');
            });
    });
