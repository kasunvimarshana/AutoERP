<?php

declare(strict_types=1);

use App\Http\Controllers\HealthController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Inventory Service API Routes
|--------------------------------------------------------------------------
*/

// Health check endpoints (no auth required)
Route::prefix('health')->group(function () {
    Route::get('/', [HealthController::class, 'check']);
    Route::get('/live', [HealthController::class, 'live']);
    Route::get('/ready', [HealthController::class, 'ready']);
});

// Product endpoints (tenant-aware)
Route::prefix('products')->middleware(['auth.service', 'tenant'])->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::post('/', [ProductController::class, 'store']);
    Route::get('/low-stock', [ProductController::class, 'lowStock']);
    Route::get('/{id}', [ProductController::class, 'show']);
    Route::put('/{id}', [ProductController::class, 'update']);
    Route::delete('/{id}', [ProductController::class, 'destroy']);

    // Saga endpoints (used by Order Service)
    Route::post('/{id}/reserve-stock', [ProductController::class, 'reserveStock']);
    Route::delete('/reservations/{reservationId}', [ProductController::class, 'releaseReservation']);
});
