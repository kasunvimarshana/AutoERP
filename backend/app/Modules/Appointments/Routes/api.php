<?php

use App\Modules\Appointments\Http\Controllers\AppointmentController;
use App\Modules\Appointments\Http\Controllers\ServiceBayController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api'])->prefix('api')->group(function () {
    // Appointment routes
    Route::prefix('appointments')->group(function () {
        Route::get('/', [AppointmentController::class, 'index']);
        Route::post('/', [AppointmentController::class, 'store']);
        Route::get('/{id}', [AppointmentController::class, 'show']);
        Route::put('/{id}', [AppointmentController::class, 'update']);
        Route::delete('/{id}', [AppointmentController::class, 'destroy']);
        
        // Additional appointment actions
        Route::post('/{id}/confirm', [AppointmentController::class, 'confirm']);
        Route::post('/{id}/start', [AppointmentController::class, 'start']);
        Route::post('/{id}/complete', [AppointmentController::class, 'complete']);
        Route::post('/{id}/cancel', [AppointmentController::class, 'cancel']);
    });

    // Service Bay routes
    Route::prefix('service-bays')->group(function () {
        Route::get('/', [ServiceBayController::class, 'index']);
        Route::post('/', [ServiceBayController::class, 'store']);
        Route::get('/{id}', [ServiceBayController::class, 'show']);
        Route::put('/{id}', [ServiceBayController::class, 'update']);
        Route::delete('/{id}', [ServiceBayController::class, 'destroy']);
    });
});
