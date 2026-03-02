<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Workflow\Interfaces\Http\Controllers\WorkflowController;

/*
|--------------------------------------------------------------------------
| Workflow Module API Routes
|--------------------------------------------------------------------------
|
| All routes are versioned under /api/v1/workflows
|
*/

Route::middleware('auth:api')->prefix('api/v1')->name('workflows.')->group(function (): void {
    Route::apiResource('workflows', WorkflowController::class);

    Route::post('workflow-instances', [WorkflowController::class, 'createInstance'])->name('instances.store');
    Route::get('workflow-instances', [WorkflowController::class, 'listInstances'])->name('instances.index');
    Route::get('workflow-instances/{id}', [WorkflowController::class, 'showInstance'])->name('instances.show');
    Route::post('workflow-instances/{id}/transition', [WorkflowController::class, 'applyTransition'])->name('instances.transition');
});
