<?php
declare(strict_types=1);
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\OrderController;
use Illuminate\Support\Facades\Route;
Route::prefix('health')->group(function () {
    Route::get('/', [HealthController::class, 'health'])->name('health');
    Route::get('/ping', [HealthController::class, 'ping'])->name('health.ping');
});
Route::prefix('v1/orders')->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/{id}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/saga', [OrderController::class, 'createSaga'])->name('orders.saga.create');
    Route::post('/saga/{orderId}/cancel', [OrderController::class, 'cancelSaga'])->name('orders.saga.cancel');
    Route::put('/{id}/confirm', [OrderController::class, 'confirm'])->name('orders.confirm');
});
