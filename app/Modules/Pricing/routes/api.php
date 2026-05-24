<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Pricing\Presentation\Http\Controllers\PricingResolutionController;
use Modules\Pricing\Presentation\Http\Controllers\PricingResourceController;

Route::prefix('api/pricing')
    ->middleware('api')
    ->name('pricing.')
    ->group(function (): void {
        Route::prefix('tenants/{tenant}')
            ->name('tenants.')
            ->group(function (): void {
                Route::post('resolve', [PricingResolutionController::class, 'resolve'])->name('resolve');

                Route::get('{resource}', [PricingResourceController::class, 'index'])->name('resources.index');
                Route::post('{resource}', [PricingResourceController::class, 'store'])->name('resources.store');
                Route::get('{resource}/{id}', [PricingResourceController::class, 'show'])->name('resources.show');
                Route::put('{resource}/{id}', [PricingResourceController::class, 'update'])->name('resources.update');
                Route::patch('{resource}/{id}', [PricingResourceController::class, 'update'])->name('resources.patch');
                Route::delete('{resource}/{id}', [PricingResourceController::class, 'destroy'])->name('resources.destroy');
            });
    });
