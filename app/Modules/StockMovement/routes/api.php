<?php
use Illuminate\Support\Facades\Route;
use Modules\StockMovement\Infrastructure\Http\Controllers\StockMovementController;
Route::prefix('api')->group(function () {
    Route::get('stock-movements/product', [StockMovementController::class, 'byProduct']);
    Route::get('stock-movements/warehouse', [StockMovementController::class, 'byWarehouse']);
    Route::post('stock-movements', [StockMovementController::class, 'store']);
});
