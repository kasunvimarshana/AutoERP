<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Pos\Interfaces\Http\Controllers\PosOrderController;
use Modules\Pos\Interfaces\Http\Controllers\PosSessionController;

Route::prefix('api/v1/pos')->group(function (): void {
    // Sessions
    Route::get('/sessions', [PosSessionController::class, 'index']);
    Route::post('/sessions', [PosSessionController::class, 'store']);
    Route::get('/sessions/{id}', [PosSessionController::class, 'show']);
    Route::put('/sessions/{id}/close', [PosSessionController::class, 'close']);
    Route::delete('/sessions/{id}', [PosSessionController::class, 'destroy']);

    // Orders
    Route::get('/orders', [PosOrderController::class, 'index']);
    Route::post('/orders', [PosOrderController::class, 'store']);
    Route::get('/orders/{id}', [PosOrderController::class, 'show']);
    Route::post('/orders/{id}/pay', [PosOrderController::class, 'pay']);
    Route::post('/orders/{id}/cancel', [PosOrderController::class, 'cancel']);
    Route::post('/orders/{id}/refund', [PosOrderController::class, 'refund']);
    Route::get('/orders/{id}/lines', [PosOrderController::class, 'lines']);
    Route::get('/orders/{id}/payments', [PosOrderController::class, 'payments']);
    Route::delete('/orders/{id}', [PosOrderController::class, 'destroy']);
});
