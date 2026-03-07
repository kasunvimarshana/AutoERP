<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Order Service
|--------------------------------------------------------------------------
*/

// Health check (public)
Route::get('/health', [HealthController::class, 'index']);

// Incoming webhooks (public – signature validated inside controller)
Route::post('/webhooks/receive', [WebhookController::class, 'receive']);

// Authenticated + tenant-scoped routes
Route::middleware(['auth:api', 'tenant'])->group(function () {

    // Orders
    Route::get('/orders',                           [OrderController::class, 'index']);
    Route::post('/orders',                          [OrderController::class, 'store']);
    Route::get('/orders/status/{status}',           [OrderController::class, 'byStatus']);
    Route::get('/orders/customer/{customerId}',     [OrderController::class, 'byCustomer']);
    Route::get('/orders/{id}',                      [OrderController::class, 'show']);
    Route::put('/orders/{id}',                      [OrderController::class, 'update']);
    Route::delete('/orders/{id}',                   [OrderController::class, 'destroy']);
    Route::patch('/orders/{id}/status',             [OrderController::class, 'updateStatus']);
});
