<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Infrastructure\Http\Controllers\CycleCountController;
use Modules\Inventory\Infrastructure\Http\Controllers\CycleCountLineController;
use Modules\Inventory\Infrastructure\Http\Controllers\StockLevelController;
use Modules\Inventory\Infrastructure\Http\Controllers\StockMovementController;

Route::prefix('api')->middleware(['auth:api'])->group(function (): void {
    Route::post('stock-levels/{id}/adjust', [StockLevelController::class, 'adjust']);
    Route::post('stock-levels/{id}/reserve', [StockLevelController::class, 'reserve']);
    Route::post('stock-levels/{id}/release', [StockLevelController::class, 'release']);
    Route::apiResource('stock-levels', StockLevelController::class);

    Route::get('stock-movements', [StockMovementController::class, 'index']);
    Route::post('stock-movements', [StockMovementController::class, 'store']);
    Route::get('stock-movements/{id}', [StockMovementController::class, 'show']);

    Route::post('cycle-counts/{id}/start', [CycleCountController::class, 'start']);
    Route::post('cycle-counts/{id}/complete', [CycleCountController::class, 'complete']);
    Route::post('cycle-counts/{id}/cancel', [CycleCountController::class, 'cancel']);
    Route::post('cycle-counts/{id}/lines', [CycleCountController::class, 'addLine']);
    Route::apiResource('cycle-counts', CycleCountController::class);

    Route::put('cycle-count-lines/{id}', [CycleCountLineController::class, 'update']);
});
