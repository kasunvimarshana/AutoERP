<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json([
    'status'  => 'ok',
    'service' => 'order-service',
    'version' => '1.0.0',
    'time'    => now()->toIso8601String(),
]));

Route::apiResource('orders', OrderController::class)->only(['index', 'show', 'store']);
Route::put('orders/{id}/cancel', [OrderController::class, 'cancel']);
