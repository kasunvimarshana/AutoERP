<?php

use Illuminate\Support\Facades\Route;
use Modules\Manufacturing\Presentation\Controllers\BomController;
use Modules\Manufacturing\Presentation\Controllers\WorkOrderController;

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::apiResource('manufacturing/boms', BomController::class);
    Route::apiResource('manufacturing/work-orders', WorkOrderController::class);
    Route::post('manufacturing/work-orders/{id}/start', [WorkOrderController::class, 'start']);
    Route::post('manufacturing/work-orders/{id}/complete', [WorkOrderController::class, 'complete']);
});
