<?php

use App\Http\Controllers\InventoryController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Inventory Service – API Routes
|--------------------------------------------------------------------------
| All routes are prefixed with /api (RouteServiceProvider).
| The X-Tenant-ID header is injected by the API Gateway.
|--------------------------------------------------------------------------
*/

Route::get('/health', [InventoryController::class, 'health'])->name('health');

Route::prefix('v1')->group(function () {
    // ---------------------------------------------------------------------------
    // Inventory / Products
    // ---------------------------------------------------------------------------

    // POST before parameterised routes to prevent /check-availability being
    // caught by the {id} wildcard.
    Route::post('/inventory/check-availability', [InventoryController::class, 'checkAvailability'])
        ->name('inventory.check-availability');

    Route::get('/inventory',       [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory',      [InventoryController::class, 'store'])->name('inventory.store');
    Route::get('/inventory/{id}',  [InventoryController::class, 'show'])->name('inventory.show');
    Route::put('/inventory/{id}',  [InventoryController::class, 'update'])->name('inventory.update');

    // ---------------------------------------------------------------------------
    // Warehouses
    // ---------------------------------------------------------------------------
    Route::get('/warehouses',        [WarehouseController::class, 'index'])->name('warehouses.index');
    Route::post('/warehouses',       [WarehouseController::class, 'store'])->name('warehouses.store');
    Route::get('/warehouses/{id}',   [WarehouseController::class, 'show'])->name('warehouses.show');
    Route::put('/warehouses/{id}',   [WarehouseController::class, 'update'])->name('warehouses.update');
    Route::delete('/warehouses/{id}', [WarehouseController::class, 'destroy'])->name('warehouses.destroy');
});
