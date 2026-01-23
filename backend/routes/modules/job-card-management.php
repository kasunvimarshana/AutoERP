<?php

use App\Modules\JobCardManagement\Http\Controllers\JobCardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Job Card Management API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/v1')->middleware(['api'])->group(function () {
    
    // Job Card routes
    Route::apiResource('job-cards', JobCardController::class);
    Route::post('job-cards/{id}/open', [JobCardController::class, 'open']);
    Route::post('job-cards/{id}/close', [JobCardController::class, 'close']);
    Route::post('job-cards/{id}/assign', [JobCardController::class, 'assign']);
});
