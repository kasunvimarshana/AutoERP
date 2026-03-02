<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Workflow\Interfaces\Http\Controllers\WorkflowDefinitionController;
use Modules\Workflow\Interfaces\Http\Controllers\WorkflowInstanceController;

Route::prefix('api/v1')->group(function (): void {
    // Workflow Definitions
    Route::get('/workflows', [WorkflowDefinitionController::class, 'index']);
    Route::post('/workflows', [WorkflowDefinitionController::class, 'store']);
    Route::get('/workflows/{id}', [WorkflowDefinitionController::class, 'show']);
    Route::put('/workflows/{id}', [WorkflowDefinitionController::class, 'update']);
    Route::delete('/workflows/{id}', [WorkflowDefinitionController::class, 'destroy']);
    Route::get('/workflows/{id}/states', [WorkflowDefinitionController::class, 'states']);
    Route::get('/workflows/{id}/transitions', [WorkflowDefinitionController::class, 'transitions']);

    // Workflow Instances
    Route::get('/workflow-instances', [WorkflowInstanceController::class, 'index']);
    Route::post('/workflow-instances', [WorkflowInstanceController::class, 'store']);
    Route::get('/workflow-instances/{id}', [WorkflowInstanceController::class, 'show']);
    Route::post('/workflow-instances/{id}/advance', [WorkflowInstanceController::class, 'advance']);
    Route::post('/workflow-instances/{id}/cancel', [WorkflowInstanceController::class, 'cancel']);
    Route::delete('/workflow-instances/{id}', [WorkflowInstanceController::class, 'destroy']);
    Route::get('/workflow-instances/{id}/logs', [WorkflowInstanceController::class, 'logs']);
});
