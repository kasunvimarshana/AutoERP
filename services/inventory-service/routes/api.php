<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\StockController;
use Illuminate\Support\Facades\Route;

// Health checks
Route::prefix('health')->group(function () {
    Route::get('/', [HealthController::class, 'health'])->name('health');
    Route::get('/ping', [HealthController::class, 'ping'])->name('health.ping');
});

// Inventory API v1
Route::prefix('v1/inventory')->group(function () {
    // Products
    Route::apiResource('products', ProductController::class);

    // Stock operations (Saga participant endpoints)
    Route::prefix('stock')->group(function () {
        Route::post('/reserve', [StockController::class, 'reserve'])->name('stock.reserve');
        Route::post('/release', [StockController::class, 'release'])->name('stock.release');
        Route::post('/deduct', [StockController::class, 'deduct'])->name('stock.deduct');
        Route::post('/restore', [StockController::class, 'restore'])->name('stock.restore');
        Route::get('/availability/{productId}/{warehouseId}/{quantity}', [StockController::class, 'availability'])
            ->name('stock.availability');
    });
});
