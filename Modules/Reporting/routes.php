<?php

use Illuminate\Support\Facades\Route;
use Modules\Reporting\Presentation\Controllers\DashboardController;
use Modules\Reporting\Presentation\Controllers\ReportController;

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::get('reporting/dashboards', [DashboardController::class, 'index']);
    Route::post('reporting/dashboards', [DashboardController::class, 'store']);
    Route::get('reporting/dashboards/{id}', [DashboardController::class, 'show']);
    Route::put('reporting/dashboards/{id}', [DashboardController::class, 'update']);
    Route::delete('reporting/dashboards/{id}', [DashboardController::class, 'destroy']);

    Route::get('reporting/reports', [ReportController::class, 'index']);
    Route::post('reporting/reports', [ReportController::class, 'store']);
    Route::get('reporting/reports/{id}', [ReportController::class, 'show']);
    Route::put('reporting/reports/{id}', [ReportController::class, 'update']);
    Route::delete('reporting/reports/{id}', [ReportController::class, 'destroy']);
});
