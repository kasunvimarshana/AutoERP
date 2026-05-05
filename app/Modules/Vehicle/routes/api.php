<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Vehicle\Infrastructure\Http\Controllers\VehicleController;
use Modules\Vehicle\Infrastructure\Http\Controllers\VehicleDashboardController;
use Modules\Vehicle\Infrastructure\Http\Controllers\VehicleJobCardController;
use Modules\Vehicle\Infrastructure\Http\Controllers\VehicleRentalController;

Route::middleware(['auth.configured', 'resolve.tenant'])->group(function (): void {
    Route::get('vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
    Route::post('vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
    Route::get('vehicles/{vehicle}', [VehicleController::class, 'show'])->name('vehicles.show');
    Route::delete('vehicles/{vehicle}', [VehicleController::class, 'destroy'])->name('vehicles.destroy');

    Route::patch('vehicles/{vehicle}/status', [VehicleDashboardController::class, 'updateStatus'])->name('vehicles.status.update');
    Route::get('vehicles-dashboard', [VehicleDashboardController::class, 'dashboard'])->name('vehicles.dashboard');

    Route::get('vehicles/{vehicle}/job-cards', [VehicleJobCardController::class, 'index'])->name('vehicle-job-cards.index');
    Route::post('vehicles/job-cards', [VehicleJobCardController::class, 'store'])->name('vehicle-job-cards.store');

    Route::get('vehicles/{vehicle}/rentals', [VehicleRentalController::class, 'index'])->name('vehicle-rentals.index');
    Route::post('vehicles/rentals', [VehicleRentalController::class, 'store'])->name('vehicle-rentals.store');
    Route::post('vehicles/rentals/{rental}/close', [VehicleRentalController::class, 'close'])->name('vehicle-rentals.close');
});
