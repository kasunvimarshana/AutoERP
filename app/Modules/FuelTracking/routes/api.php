<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\FuelTracking\Infrastructure\Http\Controllers\FuelLogController;

Route::middleware(['auth.configured', 'resolve.tenant'])->group(function (): void {
    Route::get('fuel-logs', [FuelLogController::class, 'index']);
    Route::post('fuel-logs', [FuelLogController::class, 'store']);
    Route::get('fuel-logs/{id}', [FuelLogController::class, 'show']);
    Route::delete('fuel-logs/{id}', [FuelLogController::class, 'destroy']);

    Route::get('vehicles/{vehicleId}/fuel-logs', [FuelLogController::class, 'byVehicle']);
    Route::get('drivers/{driverId}/fuel-logs', [FuelLogController::class, 'byDriver']);
});
