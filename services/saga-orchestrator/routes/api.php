<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\SagaController;
use Illuminate\Support\Facades\Route;

// Health checks
Route::prefix('health')->group(function () {
    Route::get('/', [HealthController::class, 'health'])->name('health');
    Route::get('/ping', [HealthController::class, 'ping'])->name('health.ping');
});

// Saga API v1
Route::prefix('v1/saga')->group(function () {
    Route::get('/', [SagaController::class, 'index'])->name('saga.index');
    Route::post('/', [SagaController::class, 'start'])->name('saga.start');
    Route::get('/{sagaId}', [SagaController::class, 'show'])->name('saga.show');
});
