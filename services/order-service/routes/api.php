<?php

declare(strict_types=1);

use App\Http\Controllers\HealthController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

// Health checks
Route::prefix('health')->group(function () {
    Route::get('/', [HealthController::class, 'check']);
    Route::get('/live', [HealthController::class, 'live']);
    Route::get('/ready', [HealthController::class, 'ready']);
});

// Order endpoints
Route::prefix('orders')->middleware(['auth.service', 'tenant'])->group(function () {
    Route::get('/', [OrderController::class, 'index']);
    Route::post('/', [OrderController::class, 'store']);
    Route::get('/{id}', [OrderController::class, 'show']);
    Route::post('/{id}/cancel', [OrderController::class, 'cancel']);
    Route::post('/saga/event', [OrderController::class, 'sagaEvent']);
});
