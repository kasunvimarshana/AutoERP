<?php

use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\WarehouseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Health check (no auth required)
|--------------------------------------------------------------------------
*/
Route::get('/api/v1/health', HealthController::class)->name('health');

/*
|--------------------------------------------------------------------------
| Warehouse management (tenant-scoped)
|--------------------------------------------------------------------------
*/
Route::prefix('api/v1')->middleware(['keycloak.auth', 'tenant'])->group(function () {

    // Viewers and above can list/show warehouses
    Route::get('warehouses',             [WarehouseController::class, 'index'])->name('warehouses.index');
    Route::get('warehouses/{warehouse}', [WarehouseController::class, 'show'])->name('warehouses.show');

    // Managers and above can create/update/delete warehouses
    Route::middleware('rbac:manager,admin,super-admin')->group(function () {
        Route::post('warehouses',              [WarehouseController::class, 'store'])->name('warehouses.store');
        Route::put('warehouses/{warehouse}',   [WarehouseController::class, 'update'])->name('warehouses.update');
        Route::patch('warehouses/{warehouse}', [WarehouseController::class, 'update'])->name('warehouses.patch');
        Route::delete('warehouses/{warehouse}',[WarehouseController::class, 'destroy'])->name('warehouses.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Inventory management (tenant-scoped)
|--------------------------------------------------------------------------
*/
Route::prefix('api/v1')->middleware(['keycloak.auth', 'tenant'])->group(function () {

    // Viewers and above can read inventory
    Route::get('inventory',              [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('inventory/{item}',       [InventoryController::class, 'show'])->name('inventory.show');
    Route::get('inventory/{item}/transactions', [InventoryController::class, 'transactions'])->name('inventory.transactions');

    // Managers and above can manage inventory
    Route::middleware('rbac:manager,admin,super-admin')->group(function () {
        Route::post('inventory',               [InventoryController::class, 'store'])->name('inventory.store');
        Route::put('inventory/{item}',         [InventoryController::class, 'update'])->name('inventory.update');
        Route::patch('inventory/{item}',       [InventoryController::class, 'update'])->name('inventory.patch');
        Route::delete('inventory/{item}',      [InventoryController::class, 'destroy'])->name('inventory.destroy');

        // Stock operations
        Route::post('inventory/{item}/adjust-stock',  [InventoryController::class, 'adjustStock'])->name('inventory.adjustStock');
        Route::post('inventory/{item}/reserve-stock', [InventoryController::class, 'reserveStock'])->name('inventory.reserveStock');
        Route::post('inventory/{item}/release-stock', [InventoryController::class, 'releaseStock'])->name('inventory.releaseStock');
    });
});
