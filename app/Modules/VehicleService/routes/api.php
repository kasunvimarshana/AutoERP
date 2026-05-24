<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\VehicleService\Presentation\Http\Controllers\VehicleServiceRecalculationController;
use Modules\VehicleService\Presentation\Http\Controllers\VehicleServiceResourceController;

Route::prefix('api/vehicle-service')
    ->middleware('api')
    ->name('vehicle-service.')
    ->group(function (): void {
        Route::prefix('tenants/{tenant}')
            ->name('tenants.')
            ->group(function (): void {
                Route::post('job-cards/{jobCard}/recalculate', [VehicleServiceRecalculationController::class, 'jobCard'])->name('job-cards.recalculate');

                Route::get('{resource}', [VehicleServiceResourceController::class, 'index'])->name('resources.index');
                Route::post('{resource}', [VehicleServiceResourceController::class, 'store'])->name('resources.store');
                Route::get('{resource}/{id}', [VehicleServiceResourceController::class, 'show'])->name('resources.show');
                Route::put('{resource}/{id}', [VehicleServiceResourceController::class, 'update'])->name('resources.update');
                Route::patch('{resource}/{id}', [VehicleServiceResourceController::class, 'update'])->name('resources.patch');
                Route::delete('{resource}/{id}', [VehicleServiceResourceController::class, 'destroy'])->name('resources.destroy');
            });
    });
