<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Inventory\Http\Controllers\ProductController;
use App\Modules\Inventory\Http\Controllers\SKUController;
use App\Modules\Inventory\Http\Controllers\BatchController;
use App\Modules\Inventory\Http\Controllers\StockMovementController;

Route::prefix('api/inventory')->middleware(['api'])->group(function () {
    // Product routes
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::post('/', [ProductController::class, 'store']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
    });

    // SKU routes
    Route::prefix('skus')->group(function () {
        Route::get('/', [SKUController::class, 'index']);
        Route::post('/', [SKUController::class, 'store']);
        Route::get('/{id}', [SKUController::class, 'show']);
        Route::put('/{id}', [SKUController::class, 'update']);
        Route::delete('/{id}', [SKUController::class, 'destroy']);
    });

    // Batch routes
    Route::prefix('batches')->group(function () {
        Route::get('/', [BatchController::class, 'index']);
        Route::post('/', [BatchController::class, 'store']);
        Route::get('/{id}', [BatchController::class, 'show']);
        Route::put('/{id}', [BatchController::class, 'update']);
        Route::delete('/{id}', [BatchController::class, 'destroy']);
    });

    // Stock Movement routes (append-only, no update/delete)
    Route::prefix('stock-movements')->group(function () {
        Route::get('/', [StockMovementController::class, 'index']);
        Route::post('/', [StockMovementController::class, 'store']);
        Route::get('/{id}', [StockMovementController::class, 'show']);
    });
});
