<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\StockMovement\Infrastructure\Http\Controllers\StockMovementController;

Route::prefix('stock-movements')->group(function () {
    Route::get('/', [StockMovementController::class, 'index']);
    Route::post('/', [StockMovementController::class, 'store']);
    Route::post('/transfer', [StockMovementController::class, 'transfer']);
    Route::get('/{id}', [StockMovementController::class, 'show']);
    Route::put('/{id}', [StockMovementController::class, 'update']);
    Route::delete('/{id}', [StockMovementController::class, 'destroy']);
    Route::post('/{id}/confirm', [StockMovementController::class, 'confirm']);
});
