<?php

use App\Http\Controllers\Api\V1\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'order-service',
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::prefix('v1')->middleware(['auth.passport', 'tenant'])->group(function () {
    Route::apiResource('orders', OrderController::class);
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
    Route::get('/orders/{id}/saga-log', [OrderController::class, 'sagaLog']);
});
