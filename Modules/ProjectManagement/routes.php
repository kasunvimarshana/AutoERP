<?php

use Illuminate\Support\Facades\Route;
use Modules\ProjectManagement\Presentation\Controllers\MilestoneController;
use Modules\ProjectManagement\Presentation\Controllers\ProjectController;
use Modules\ProjectManagement\Presentation\Controllers\TaskController;
use Modules\ProjectManagement\Presentation\Controllers\TimeEntryController;

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::apiResource('pm/projects', ProjectController::class);
    Route::apiResource('pm/tasks', TaskController::class);
    Route::post('pm/tasks/{id}/complete', [TaskController::class, 'complete']);
    Route::apiResource('pm/milestones', MilestoneController::class);
    Route::apiResource('pm/time-entries', TimeEntryController::class);
});
