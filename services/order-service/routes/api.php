<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::get('/orders',              [OrderController::class, 'index']);
    Route::post('/orders',             [OrderController::class, 'store']);
    Route::get('/orders/{id}',         [OrderController::class, 'show']);
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
});

// Internal Saga callback endpoint (service-to-service, no user auth required)
Route::post('/orders/{id}/saga-callback', [OrderController::class, 'sagaCallback'])
    ->middleware('internal');

// Health check
Route::get('/health', fn () => response()->json([
    'service'   => 'order-service',
    'status'    => 'healthy',
    'timestamp' => now()->toIso8601String(),
]));
