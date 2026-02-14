<?php

use Illuminate\Support\Facades\Route;
use App\Modules\JobCards\Http\Controllers\JobCardController;
use App\Modules\JobCards\Http\Controllers\JobCardTaskController;

Route::prefix('api/job-cards')->middleware(['api'])->group(function () {
    // Job Card routes
    Route::prefix('cards')->group(function () {
        Route::get('/', [JobCardController::class, 'index']);
        Route::post('/', [JobCardController::class, 'store']);
        Route::get('/{id}', [JobCardController::class, 'show']);
        Route::put('/{id}', [JobCardController::class, 'update']);
        Route::delete('/{id}', [JobCardController::class, 'destroy']);
        
        // Action routes
        Route::post('/{id}/start-work', [JobCardController::class, 'startWork']);
        Route::post('/{id}/close', [JobCardController::class, 'close']);
        Route::post('/{id}/cancel', [JobCardController::class, 'cancel']);
        
        // Query routes
        Route::get('/status/filter', [JobCardController::class, 'getByStatus']);
        Route::get('/customer/{customerId}', [JobCardController::class, 'getByCustomer']);
        Route::get('/vehicle/{vehicleId}', [JobCardController::class, 'getByVehicle']);
        Route::get('/technician/{technicianId}', [JobCardController::class, 'getByTechnician']);
        Route::get('/date-range/filter', [JobCardController::class, 'getByDateRange']);
        Route::get('/analytics/total-cost', [JobCardController::class, 'getTotalCost']);
    });

    // Job Card Task routes
    Route::prefix('tasks')->group(function () {
        Route::get('/', [JobCardTaskController::class, 'index']);
        Route::post('/', [JobCardTaskController::class, 'store']);
        Route::get('/{id}', [JobCardTaskController::class, 'show']);
        Route::put('/{id}', [JobCardTaskController::class, 'update']);
        Route::delete('/{id}', [JobCardTaskController::class, 'destroy']);
        
        // Action routes
        Route::post('/{id}/start', [JobCardTaskController::class, 'startTask']);
        Route::post('/{id}/complete', [JobCardTaskController::class, 'completeTask']);
        
        // Query routes
        Route::get('/job-card/{jobCardId}', [JobCardTaskController::class, 'getByJobCard']);
        Route::get('/task-type/filter', [JobCardTaskController::class, 'getByTaskType']);
        Route::get('/status/filter', [JobCardTaskController::class, 'getByStatus']);
    });
});
