<?php

use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\CategoryController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'product-service',
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::prefix('v1')->middleware(['auth.passport', 'tenant'])->group(function () {
    // Products
    Route::apiResource('products', ProductController::class);
    Route::get('/products/{id}/inventory', [ProductController::class, 'inventory']);

    // Categories
    Route::apiResource('categories', CategoryController::class);

    // Internal webhook endpoint (called by other services)
    Route::post('/webhooks/inventory-updated', [\App\Http\Controllers\Api\V1\WebhookController::class, 'inventoryUpdated']);
});
