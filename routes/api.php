<?php

use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Health check
    Route::get('/health', function () {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String()
        ]);
    });

    // Product API endpoints
    Route::apiResource('products', ProductController::class);
    Route::delete('products/bulk', [ProductController::class, 'bulkDestroy']);
    Route::get('products/active', [ProductController::class, 'active']);
    Route::get('products/low-stock', [ProductController::class, 'lowStock']);
    Route::post('products/{id}/stock', [ProductController::class, 'updateStock']);
});
