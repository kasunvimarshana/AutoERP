<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Reporting\Interfaces\Http\Controllers\ReportingController;

Route::prefix('api/v1')->middleware('api')->group(function () {
    Route::get('reporting/definitions', [ReportingController::class, 'index']);
    Route::post('reporting/definitions', [ReportingController::class, 'store']);
    Route::put('reporting/definitions/{id}', [ReportingController::class, 'updateDefinition']);
    Route::delete('reporting/definitions/{id}', [ReportingController::class, 'deleteDefinition']);
    Route::post('reporting/generate', [ReportingController::class, 'generate']);
    Route::get('reporting/schedules', [ReportingController::class, 'listSchedules']);
    Route::post('reporting/schedules', [ReportingController::class, 'schedule']);
    Route::get('reporting/schedules/{id}', [ReportingController::class, 'showSchedule']);
    Route::put('reporting/schedules/{id}', [ReportingController::class, 'updateSchedule']);
    Route::delete('reporting/schedules/{id}', [ReportingController::class, 'deleteSchedule']);
    Route::get('reporting/exports', [ReportingController::class, 'listExports']);
    Route::get('reporting/exports/{id}', [ReportingController::class, 'showExport']);
});
