<?php

declare(strict_types=1);

use App\Http\Controllers\HealthController;
use App\Http\Controllers\SagaController;
use Illuminate\Support\Facades\Route;

// Health checks
Route::prefix('health')->group(function () {
    Route::get('/', [HealthController::class, 'check']);
    Route::get('/live', [HealthController::class, 'live']);
    Route::get('/ready', [HealthController::class, 'ready']);
});

// Saga management endpoints
Route::prefix('sagas')->group(function () {
    Route::get('/', [SagaController::class, 'index']);
    Route::get('/{sagaId}', [SagaController::class, 'status']);
    Route::post('/{sagaId}/compensate', [SagaController::class, 'compensate']);
});
