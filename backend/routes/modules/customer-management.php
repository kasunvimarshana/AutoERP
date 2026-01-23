<?php

use App\Modules\CustomerManagement\Http\Controllers\CustomerController;
use App\Modules\CustomerManagement\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer Management API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/v1')->middleware(['api'])->group(function () {
    
    // Customer routes
    Route::apiResource('customers', CustomerController::class);
    Route::get('customers/upcoming-services', [CustomerController::class, 'upcomingServices']);

    // Vehicle routes
    Route::apiResource('vehicles', VehicleController::class);
    Route::post('vehicles/{id}/transfer-ownership', [VehicleController::class, 'transferOwnership']);
    Route::post('vehicles/{id}/update-mileage', [VehicleController::class, 'updateMileage']);
});
