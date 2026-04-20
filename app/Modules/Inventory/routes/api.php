<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Infrastructure\Http\Controllers\InventoryCycleCountController;
use Modules\Inventory\Infrastructure\Http\Controllers\InventoryStockReservationController;
use Modules\Inventory\Infrastructure\Http\Controllers\InventoryStockController;
use Modules\Inventory\Infrastructure\Http\Controllers\InventoryTransferOrderController;

Route::prefix('inventory')
    ->middleware(['auth:api', 'resolve.tenant'])
    ->group(function (): void {
        Route::get('warehouses/{warehouse}/movements', [InventoryStockController::class, 'movements'])
            ->name('inventory.warehouses.movements.index');
        Route::post('warehouses/{warehouse}/movements', [InventoryStockController::class, 'storeMovement'])
            ->name('inventory.warehouses.movements.store');
        Route::get('warehouses/{warehouse}/stock-levels', [InventoryStockController::class, 'stockLevels'])
            ->name('inventory.warehouses.stock-levels.index');

        Route::get('transfer-orders', [InventoryTransferOrderController::class, 'index'])
            ->name('inventory.transfer-orders.index');
        Route::post('transfer-orders', [InventoryTransferOrderController::class, 'store'])
            ->name('inventory.transfer-orders.store');
        Route::get('transfer-orders/{transferOrder}', [InventoryTransferOrderController::class, 'show'])
            ->name('inventory.transfer-orders.show');
        Route::post('transfer-orders/{transferOrder}/approve', [InventoryTransferOrderController::class, 'approve'])
            ->name('inventory.transfer-orders.approve');
        Route::post('transfer-orders/{transferOrder}/receive', [InventoryTransferOrderController::class, 'receive'])
            ->name('inventory.transfer-orders.receive');

        Route::get('cycle-counts', [InventoryCycleCountController::class, 'index'])
            ->name('inventory.cycle-counts.index');
        Route::post('cycle-counts', [InventoryCycleCountController::class, 'store'])
            ->name('inventory.cycle-counts.store');
        Route::get('cycle-counts/{cycleCount}', [InventoryCycleCountController::class, 'show'])
            ->name('inventory.cycle-counts.show');
        Route::post('cycle-counts/{cycleCount}/start', [InventoryCycleCountController::class, 'start'])
            ->name('inventory.cycle-counts.start');
        Route::post('cycle-counts/{cycleCount}/complete', [InventoryCycleCountController::class, 'complete'])
            ->name('inventory.cycle-counts.complete');

        Route::get('stock-reservations', [InventoryStockReservationController::class, 'index'])
            ->name('inventory.stock-reservations.index');
        Route::post('stock-reservations', [InventoryStockReservationController::class, 'store'])
            ->name('inventory.stock-reservations.store');
        Route::get('stock-reservations/{reservation}', [InventoryStockReservationController::class, 'show'])
            ->name('inventory.stock-reservations.show');
        Route::delete('stock-reservations/{reservation}', [InventoryStockReservationController::class, 'destroy'])
            ->name('inventory.stock-reservations.destroy');
        Route::post('stock-reservations/release-expired', [InventoryStockReservationController::class, 'releaseExpired'])
            ->name('inventory.stock-reservations.release-expired');
    });
