<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Appointment\Http\Controllers\AppointmentController;
use Modules\Appointment\Http\Controllers\BayController;

/*
|--------------------------------------------------------------------------
| Appointment Module API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the Appointment module.
| All routes are prefixed with 'api/v1' and require authentication.
|
*/

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Appointment Routes - Non-parameterized routes MUST come before apiResource
    Route::get('appointments/upcoming', [AppointmentController::class, 'upcoming'])
        ->name('appointments.upcoming');
    Route::get('appointments/search', [AppointmentController::class, 'search'])
        ->name('appointments.search');
    Route::get('appointments/by-status', [AppointmentController::class, 'byStatus'])
        ->name('appointments.by-status');
    Route::post('appointments/check-availability', [AppointmentController::class, 'checkAvailability'])
        ->name('appointments.check-availability');

    // Appointment CRUD
    Route::apiResource('appointments', AppointmentController::class);

    // Additional Appointment Routes
    Route::post('appointments/{id}/confirm', [AppointmentController::class, 'confirm'])
        ->name('appointments.confirm');
    Route::post('appointments/{id}/start', [AppointmentController::class, 'start'])
        ->name('appointments.start');
    Route::post('appointments/{id}/complete', [AppointmentController::class, 'complete'])
        ->name('appointments.complete');
    Route::post('appointments/{id}/cancel', [AppointmentController::class, 'cancel'])
        ->name('appointments.cancel');
    Route::post('appointments/{id}/reschedule', [AppointmentController::class, 'reschedule'])
        ->name('appointments.reschedule');
    Route::post('appointments/{id}/assign-bay', [AppointmentController::class, 'assignBay'])
        ->name('appointments.assign-bay');

    // Bay Routes
    Route::get('bays/available-for-branch', [BayController::class, 'availableForBranch'])
        ->name('bays.available-for-branch');
    Route::get('bays/available-for-time-range', [BayController::class, 'availableForTimeRange'])
        ->name('bays.available-for-time-range');

    // Bay CRUD
    Route::apiResource('bays', BayController::class);
});
