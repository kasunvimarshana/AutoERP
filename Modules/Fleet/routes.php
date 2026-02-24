<?php

use Illuminate\Support\Facades\Route;
use Modules\Fleet\Presentation\Controllers\MaintenanceRecordController;
use Modules\Fleet\Presentation\Controllers\VehicleController;

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::apiResource('fleet/vehicles', VehicleController::class)->except(['update']);
    Route::put('fleet/vehicles/{id}', [VehicleController::class, 'update']);
    Route::post('fleet/vehicles/{id}/retire', [VehicleController::class, 'retire']);
    Route::get('fleet/vehicles/{vehicleId}/maintenance', [MaintenanceRecordController::class, 'index']);
    Route::post('fleet/vehicles/{vehicleId}/maintenance', [MaintenanceRecordController::class, 'store']);
    Route::get('fleet/vehicles/{vehicleId}/maintenance/{id}', [MaintenanceRecordController::class, 'show']);
    Route::delete('fleet/vehicles/{vehicleId}/maintenance/{id}', [MaintenanceRecordController::class, 'destroy']);
});
