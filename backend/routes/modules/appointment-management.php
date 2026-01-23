<?php

use App\Modules\AppointmentManagement\Http\Controllers\AppointmentController;
use App\Modules\AppointmentManagement\Http\Controllers\ServiceBayController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Appointment Management API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/v1')->middleware(['api'])->group(function () {
    
    // Service Bay routes
    Route::apiResource('service-bays', ServiceBayController::class);
    Route::get('service-bays/availability/check', [ServiceBayController::class, 'checkAvailability']);

    // Appointment routes
    Route::apiResource('appointments', AppointmentController::class);
    Route::post('appointments/{id}/confirm', [AppointmentController::class, 'confirm']);
    Route::post('appointments/{id}/cancel', [AppointmentController::class, 'cancel']);
    Route::post('appointments/{id}/complete', [AppointmentController::class, 'complete']);
});
