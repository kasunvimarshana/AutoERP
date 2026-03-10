<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

// Called by Order Service (service-to-service)
Route::post('/payments/charge',      [PaymentController::class, 'charge']);
Route::post('/payments/{id}/refund', [PaymentController::class, 'refund']);
Route::get('/payments/{id}',         [PaymentController::class, 'show']);

// Health check
Route::get('/health', fn () => response()->json([
    'service'   => 'payment-service',
    'status'    => 'healthy',
    'timestamp' => now()->toIso8601String(),
]));
