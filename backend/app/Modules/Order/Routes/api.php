<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Order\Controllers\OrderController;

Route::middleware(['auth:api', 'tenant'])->prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'index']);
    Route::post('/', [OrderController::class, 'store']);
    Route::get('/{id}', [OrderController::class, 'show']);
    Route::put('/{id}', [OrderController::class, 'update']);
    Route::delete('/{id}', [OrderController::class, 'destroy']);
    Route::post('/{id}/cancel', [OrderController::class, 'cancel']);
});
