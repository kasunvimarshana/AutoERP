<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Interfaces\Http\Controllers\InventoryController;

/*
|--------------------------------------------------------------------------
| Inventory Module API Routes
|--------------------------------------------------------------------------
|
| All routes are versioned under /api/v1/inventory
|
*/

Route::middleware('auth:api')->prefix('api/v1')->name('inventory.')->group(function (): void {
    Route::get('inventory/stock', [InventoryController::class, 'listStockItems'])->name('stock.index');
    Route::post('inventory/transactions', [InventoryController::class, 'recordTransaction'])->name('transactions.store');
    Route::get('inventory/stock/{productId}/{warehouseId}', [InventoryController::class, 'getStockLevel'])->name('stock.show');
    Route::post('inventory/reservations', [InventoryController::class, 'reserve'])->name('reservations.store');
    Route::delete('inventory/reservations/{id}', [InventoryController::class, 'releaseReservation'])->name('reservations.destroy');
    Route::get('inventory/products/{productId}/transactions', [InventoryController::class, 'listTransactions'])->name('transactions.index');
    Route::get('inventory/fefo/{productId}/{warehouseId}', [InventoryController::class, 'getStockByFEFO'])->name('stock.fefo');

    // Batch / Lot management — full CRUD
    Route::post('inventory/batches', [InventoryController::class, 'createBatch'])->name('batches.store');
    Route::get('inventory/batches/{id}', [InventoryController::class, 'showBatch'])->name('batches.show');
    Route::patch('inventory/batches/{id}', [InventoryController::class, 'updateBatch'])->name('batches.update');
    Route::delete('inventory/batches/{id}', [InventoryController::class, 'deleteBatch'])->name('batches.destroy');
    Route::post('inventory/batches/deduct', [InventoryController::class, 'deductByStrategy'])->name('batches.deduct');
});
