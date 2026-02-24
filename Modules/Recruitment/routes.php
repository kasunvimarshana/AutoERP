<?php

use Illuminate\Support\Facades\Route;
use Modules\Recruitment\Presentation\Controllers\JobApplicationController;
use Modules\Recruitment\Presentation\Controllers\JobPositionController;

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::apiResource('recruitment/positions', JobPositionController::class);
    Route::apiResource('recruitment/applications', JobApplicationController::class)->except(['update']);
    Route::post('recruitment/applications/{id}/hire', [JobApplicationController::class, 'hire']);
    Route::post('recruitment/applications/{id}/reject', [JobApplicationController::class, 'reject']);
});
