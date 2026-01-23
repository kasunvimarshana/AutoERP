<?php

use App\Modules\FleetManagement\Http\Controllers\FleetController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Fleet Management API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    
    // Fleet routes
    Route::apiResource('fleets', FleetController::class);
    Route::post('fleets/{id}/vehicles', [FleetController::class, 'addVehicle']);
    Route::delete('fleets/{id}/vehicles/{vehicleId}', [FleetController::class, 'removeVehicle']);
});
