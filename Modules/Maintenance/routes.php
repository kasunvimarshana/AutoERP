<?php

use Illuminate\Support\Facades\Route;
use Modules\Maintenance\Presentation\Controllers\EquipmentController;
use Modules\Maintenance\Presentation\Controllers\MaintenanceOrderController;
use Modules\Maintenance\Presentation\Controllers\MaintenanceRequestController;

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    // Equipment
    Route::apiResource('maintenance/equipment', EquipmentController::class)->except(['update']);
    Route::put('maintenance/equipment/{id}', [EquipmentController::class, 'update']);
    Route::post('maintenance/equipment/{id}/decommission', [EquipmentController::class, 'decommission']);

    // Maintenance Requests
    Route::apiResource('maintenance/requests', MaintenanceRequestController::class)->only(['index', 'store', 'show', 'destroy']);

    // Maintenance Orders
    Route::apiResource('maintenance/orders', MaintenanceOrderController::class)->only(['index', 'store', 'show', 'destroy']);
    Route::post('maintenance/orders/{id}/start', [MaintenanceOrderController::class, 'start']);
    Route::post('maintenance/orders/{id}/complete', [MaintenanceOrderController::class, 'complete']);
});
