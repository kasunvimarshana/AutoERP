<?php

use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'inventory-service',
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::prefix('v1')->middleware(['auth.passport', 'tenant'])->group(function () {
    // Inventory CRUD
    Route::apiResource('inventory', InventoryController::class);
    Route::patch('/inventory/{id}/adjust', [InventoryController::class, 'adjust']);

    // Saga participant endpoints (called internally by order-service)
    Route::post('/inventory/reserve', [InventoryController::class, 'reserve']);
    Route::post('/inventory/release', [InventoryController::class, 'release']);

    // Warehouses
    Route::apiResource('warehouses', WarehouseController::class);
});
