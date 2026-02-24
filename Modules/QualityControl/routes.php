<?php

use Illuminate\Support\Facades\Route;
use Modules\QualityControl\Presentation\Controllers\InspectionController;
use Modules\QualityControl\Presentation\Controllers\QualityAlertController;
use Modules\QualityControl\Presentation\Controllers\QualityPointController;

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::apiResource('qc/quality-points', QualityPointController::class);
    Route::get('qc/inspections', [InspectionController::class, 'index']);
    Route::post('qc/inspections', [InspectionController::class, 'store']);
    Route::get('qc/inspections/{id}', [InspectionController::class, 'show']);
    Route::delete('qc/inspections/{id}', [InspectionController::class, 'destroy']);
    Route::post('qc/inspections/{id}/pass', [InspectionController::class, 'pass']);
    Route::post('qc/inspections/{id}/fail', [InspectionController::class, 'fail']);
    Route::get('qc/alerts', [QualityAlertController::class, 'index']);
    Route::post('qc/alerts', [QualityAlertController::class, 'store']);
    Route::get('qc/alerts/{id}', [QualityAlertController::class, 'show']);
    Route::delete('qc/alerts/{id}', [QualityAlertController::class, 'destroy']);
});
