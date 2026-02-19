<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\JobCard\Http\Controllers\JobCardController;

/*
|--------------------------------------------------------------------------
| JobCard Module API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the JobCard module.
| All routes are prefixed with 'api/v1' and require authentication.
|
*/

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Job Card CRUD
    Route::apiResource('job-cards', JobCardController::class);

    // Job Card Workflow Actions
    Route::post('job-cards/{id}/start', [JobCardController::class, 'start'])
        ->name('job-cards.start');
    Route::post('job-cards/{id}/pause', [JobCardController::class, 'pause'])
        ->name('job-cards.pause');
    Route::post('job-cards/{id}/resume', [JobCardController::class, 'resume'])
        ->name('job-cards.resume');
    Route::post('job-cards/{id}/complete', [JobCardController::class, 'complete'])
        ->name('job-cards.complete');
    Route::patch('job-cards/{id}/status', [JobCardController::class, 'updateStatus'])
        ->name('job-cards.update-status');

    // Job Card Assignment
    Route::post('job-cards/{id}/assign-technician', [JobCardController::class, 'assignTechnician'])
        ->name('job-cards.assign-technician');

    // Job Card Calculations
    Route::post('job-cards/{id}/calculate-totals', [JobCardController::class, 'calculateTotals'])
        ->name('job-cards.calculate-totals');

    // Job Card Statistics
    Route::get('job-cards/{id}/statistics', [JobCardController::class, 'statistics'])
        ->name('job-cards.statistics');

    // Job Card Tasks
    Route::get('job-cards/{id}/tasks', [JobCardController::class, 'getTasks'])
        ->name('job-cards.tasks.index');
    Route::post('job-cards/{id}/tasks', [JobCardController::class, 'addTask'])
        ->name('job-cards.tasks.store');
    Route::delete('job-cards/{id}/tasks/{taskId}', [JobCardController::class, 'removeTask'])
        ->name('job-cards.tasks.destroy');

    // Job Card Inspection Items
    Route::get('job-cards/{id}/inspections', [JobCardController::class, 'getInspections'])
        ->name('job-cards.inspections.index');
    Route::post('job-cards/{id}/inspections', [JobCardController::class, 'addInspection'])
        ->name('job-cards.inspections.store');

    // Job Card Parts
    Route::get('job-cards/{id}/parts', [JobCardController::class, 'getParts'])
        ->name('job-cards.parts.index');
    Route::post('job-cards/{id}/parts', [JobCardController::class, 'addPart'])
        ->name('job-cards.parts.store');
    Route::delete('job-cards/{id}/parts/{partId}', [JobCardController::class, 'removePart'])
        ->name('job-cards.parts.destroy');
});
