<?php

declare(strict_types=1);

use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::get('/health', fn () => response()->json(['status' => 'ok', 'service' => 'inventory-service']));

    Route::middleware('tenant')->group(function () {

        // Product catalogue CRUD
        Route::apiResource('products', ProductController::class);

        // Inventory management
        Route::get('inventory', [InventoryController::class, 'index']);
        Route::post('inventory/reserve', [InventoryController::class, 'reserve']);
        Route::post('inventory/release', [InventoryController::class, 'release']);
    });
});
