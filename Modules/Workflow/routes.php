<?php

use Illuminate\Support\Facades\Route;
use Modules\Workflow\Presentation\Controllers\WorkflowController;

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::get('workflows', [WorkflowController::class, 'index']);
    Route::post('workflows', [WorkflowController::class, 'store']);
    Route::get('workflows/{id}', [WorkflowController::class, 'show']);
    Route::put('workflows/{id}', [WorkflowController::class, 'update']);
    Route::delete('workflows/{id}', [WorkflowController::class, 'destroy']);
    Route::post('workflows/{id}/transition', [WorkflowController::class, 'transition']);
    Route::get('workflows/{id}/history', [WorkflowController::class, 'history']);
});
