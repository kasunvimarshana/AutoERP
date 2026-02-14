<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Fleet\Http\Controllers\FleetVehicleController;
use App\Modules\Fleet\Http\Controllers\MaintenanceRecordController;

Route::prefix('api/fleet')->middleware(['api'])->group(function () {
    // Fleet Vehicle routes
    Route::prefix('vehicles')->group(function () {
        Route::get('/', [FleetVehicleController::class, 'index']);
        Route::post('/', [FleetVehicleController::class, 'store']);
        Route::get('/{id}', [FleetVehicleController::class, 'show']);
        Route::put('/{id}', [FleetVehicleController::class, 'update']);
        Route::delete('/{id}', [FleetVehicleController::class, 'destroy']);
        
        // Action routes
        Route::post('/{id}/mileage', [FleetVehicleController::class, 'updateMileage']);
        Route::post('/{id}/status', [FleetVehicleController::class, 'updateStatus']);
        Route::post('/{id}/service', [FleetVehicleController::class, 'recordService']);
        
        // Query routes
        Route::get('/status/filter', [FleetVehicleController::class, 'getByStatus']);
        Route::get('/fuel-type/filter', [FleetVehicleController::class, 'getByFuelType']);
        Route::get('/make/filter', [FleetVehicleController::class, 'getByMake']);
        Route::get('/year/filter', [FleetVehicleController::class, 'getByYear']);
        Route::get('/service-due/filter', [FleetVehicleController::class, 'getServiceDue']);
        Route::get('/tenant-status/filter', [FleetVehicleController::class, 'getByTenantAndStatus']);
        Route::get('/analytics/total-mileage', [FleetVehicleController::class, 'getTotalMileage']);
        Route::get('/analytics/vehicle-count', [FleetVehicleController::class, 'getVehicleCount']);
    });

    // Maintenance Record routes
    Route::prefix('maintenance-records')->group(function () {
        Route::get('/', [MaintenanceRecordController::class, 'index']);
        Route::post('/', [MaintenanceRecordController::class, 'store']);
        Route::get('/{id}', [MaintenanceRecordController::class, 'show']);
        Route::put('/{id}', [MaintenanceRecordController::class, 'update']);
        Route::delete('/{id}', [MaintenanceRecordController::class, 'destroy']);
        
        // Action routes
        Route::post('/{id}/complete', [MaintenanceRecordController::class, 'complete']);
        Route::post('/{id}/cancel', [MaintenanceRecordController::class, 'cancel']);
        
        // Query routes
        Route::get('/vehicle/{vehicleId}', [MaintenanceRecordController::class, 'getByFleetVehicle']);
        Route::get('/vehicle/{vehicleId}/recent', [MaintenanceRecordController::class, 'getRecentMaintenance']);
        Route::get('/vehicle/{vehicleId}/cost', [MaintenanceRecordController::class, 'getCostByVehicle']);
        Route::get('/maintenance-type/filter', [MaintenanceRecordController::class, 'getByMaintenanceType']);
        Route::get('/status/filter', [MaintenanceRecordController::class, 'getByStatus']);
        Route::get('/date-range/filter', [MaintenanceRecordController::class, 'getByDateRange']);
        Route::get('/upcoming/filter', [MaintenanceRecordController::class, 'getUpcomingMaintenance']);
        Route::get('/analytics/total-cost', [MaintenanceRecordController::class, 'getTotalCost']);
        Route::get('/analytics/cost-by-type', [MaintenanceRecordController::class, 'getCostByMaintenanceType']);
    });
});
