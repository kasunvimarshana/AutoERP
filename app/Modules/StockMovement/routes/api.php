<?php
use Illuminate\Support\Facades\Route;
use Modules\StockMovement\Infrastructure\Http\Controllers\StockMovementController;

Route::prefix('stock-movements')->group(function () {
    Route::get('/',          [StockMovementController::class, 'index']);
    Route::post('/',         [StockMovementController::class, 'store']);
    Route::get('/{id}',      [StockMovementController::class, 'show']);
    Route::post('/transfer', [StockMovementController::class, 'transfer']);
});
