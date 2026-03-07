<?php

use App\Http\Controllers\InventoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Inventory Service API Routes
|--------------------------------------------------------------------------
|
| Routes for Inventory Service CRUD operations and cross-service
| transaction endpoints (reserve, release, fulfill).
|
*/

// Standard CRUD routes for inventory management
Route::apiResource('inventories', InventoryController::class);

// Cross-service transaction endpoints (called by Order Service)
Route::prefix('inventories')->group(function () {
    // Reserve inventory for a new order (part of distributed transaction)
    Route::post('/reserve', [InventoryController::class, 'reserve']);

    // Release (compensating transaction) reserved inventory when order fails
    Route::post('/release', [InventoryController::class, 'release']);

    // Fulfill inventory when an order is completed
    Route::post('/fulfill', [InventoryController::class, 'fulfill']);
});

