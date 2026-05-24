<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\VehicleRental\Presentation\Http\Controllers\VehicleRentalRecalculationController;
use Modules\VehicleRental\Presentation\Http\Controllers\VehicleRentalResourceController;

Route::prefix('api/vehicle-rental')
    ->middleware('api')
    ->name('vehicle-rental.')
    ->group(function (): void {
        Route::prefix('tenants/{tenant}')
            ->name('tenants.')
            ->group(function (): void {
                Route::post('lessor-running-charts/{runningChart}/recalculate', [VehicleRentalRecalculationController::class, 'lessorRunningChart'])->name('lessor-running-charts.recalculate');
                Route::post('lessee-running-charts/{runningChart}/recalculate', [VehicleRentalRecalculationController::class, 'lesseeRunningChart'])->name('lessee-running-charts.recalculate');

                Route::get('{resource}', [VehicleRentalResourceController::class, 'index'])->name('resources.index');
                Route::post('{resource}', [VehicleRentalResourceController::class, 'store'])->name('resources.store');
                Route::get('{resource}/{id}', [VehicleRentalResourceController::class, 'show'])->name('resources.show');
                Route::put('{resource}/{id}', [VehicleRentalResourceController::class, 'update'])->name('resources.update');
                Route::patch('{resource}/{id}', [VehicleRentalResourceController::class, 'update'])->name('resources.patch');
                Route::delete('{resource}/{id}', [VehicleRentalResourceController::class, 'destroy'])->name('resources.destroy');
            });
    });
