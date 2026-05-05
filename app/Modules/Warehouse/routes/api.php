<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Warehouse\Infrastructure\Http\Controllers\WarehouseController;
use Modules\Warehouse\Infrastructure\Http\Controllers\WarehouseLocationController;
use Modules\Warehouse\Infrastructure\Http\Controllers\WarehouseStockController;

Route::middleware(['auth.configured', 'resolve.tenant'])->group(function (): void {
    Route::get('warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
    Route::post('warehouses', [WarehouseController::class, 'store'])->name('warehouses.store');
    Route::get('warehouses/{warehouse}', [WarehouseController::class, 'show'])->name('warehouses.show');
    Route::put('warehouses/{warehouse}', [WarehouseController::class, 'update'])->name('warehouses.update');
    Route::delete('warehouses/{warehouse}', [WarehouseController::class, 'destroy'])->name('warehouses.destroy');

    Route::get('warehouses/{warehouse}/locations', [WarehouseLocationController::class, 'index'])->name('warehouse-locations.index');
    Route::post('warehouses/{warehouse}/locations', [WarehouseLocationController::class, 'store'])->name('warehouse-locations.store');
    Route::get('warehouses/{warehouse}/locations/{location}', [WarehouseLocationController::class, 'show'])->name('warehouse-locations.show');
    Route::put('warehouses/{warehouse}/locations/{location}', [WarehouseLocationController::class, 'update'])->name('warehouse-locations.update');
    Route::delete('warehouses/{warehouse}/locations/{location}', [WarehouseLocationController::class, 'destroy'])->name('warehouse-locations.destroy');

    Route::get('warehouses/{warehouse}/stock-movements', [WarehouseStockController::class, 'movements'])->name('warehouse-stock-movements.index');
    Route::post('warehouses/{warehouse}/stock-movements', [WarehouseStockController::class, 'storeMovement'])->name('warehouse-stock-movements.store');
    Route::get('warehouses/{warehouse}/stock-levels', [WarehouseStockController::class, 'stockLevels'])->name('warehouse-stock-levels.index');
});
