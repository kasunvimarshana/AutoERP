<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Workflow\Http\Controllers\ApprovalController;
use Modules\Workflow\Http\Controllers\WorkflowController;
use Modules\Workflow\Http\Controllers\WorkflowInstanceController;

Route::prefix('api/v1')
    ->middleware(['api', 'auth:sanctum'])
    ->group(function () {

        Route::prefix('workflows')->group(function () {
            Route::get('/', [WorkflowController::class, 'index']);
            Route::post('/', [WorkflowController::class, 'store']);
            Route::get('/{workflow}', [WorkflowController::class, 'show']);
            Route::put('/{workflow}', [WorkflowController::class, 'update']);
            Route::delete('/{workflow}', [WorkflowController::class, 'destroy']);

            Route::post('/{workflow}/execute', [WorkflowController::class, 'execute']);
            Route::post('/{workflow}/activate', [WorkflowController::class, 'activate']);
            Route::post('/{workflow}/deactivate', [WorkflowController::class, 'deactivate']);
            Route::post('/{workflow}/duplicate', [WorkflowController::class, 'duplicate']);
        });

        Route::prefix('workflow-instances')->group(function () {
            Route::get('/', [WorkflowInstanceController::class, 'index']);
            Route::get('/{workflowInstance}', [WorkflowInstanceController::class, 'show']);
            Route::post('/{workflowInstance}/cancel', [WorkflowInstanceController::class, 'cancel']);
            Route::post('/{workflowInstance}/resume', [WorkflowInstanceController::class, 'resume']);
        });

        Route::prefix('approvals')->group(function () {
            Route::get('/', [ApprovalController::class, 'index']);
            Route::get('/pending', [ApprovalController::class, 'pending']);
            Route::get('/{approval}', [ApprovalController::class, 'show']);
            Route::post('/{approval}/respond', [ApprovalController::class, 'respond']);
            Route::post('/{approval}/delegate', [ApprovalController::class, 'delegate']);
        });
    });
