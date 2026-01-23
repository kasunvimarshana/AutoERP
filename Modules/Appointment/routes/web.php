<?php

use Illuminate\Support\Facades\Route;
use Modules\Appointment\Http\Controllers\AppointmentController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('appointments', AppointmentController::class)->names('appointment');
});
