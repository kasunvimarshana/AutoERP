<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Infrastructure\Http\Controllers\InventoryBatchController;
use Modules\Inventory\Infrastructure\Http\Controllers\InventoryCycleCountController;
use Modules\Inventory\Infrastructure\Http\Controllers\InventoryStockController;
use Modules\Inventory\Infrastructure\Http\Controllers\InventoryStockReservationController;
use Modules\Inventory\Infrastructure\Http\Controllers\InventoryTransferOrderController;
use Modules\Inventory\Infrastructure\Http\Controllers\InventoryValuationController;

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

        // Valuation configuration
        Route::get('valuation-configs', [InventoryValuationController::class, 'index'])
            ->name('inventory.valuation-configs.index');
        Route::post('valuation-configs', [InventoryValuationController::class, 'store'])
            ->name('inventory.valuation-configs.store');
        Route::get('valuation-configs/resolve', [InventoryValuationController::class, 'resolve'])
            ->name('inventory.valuation-configs.resolve');
        Route::get('valuation-configs/{config}', [InventoryValuationController::class, 'show'])
            ->name('inventory.valuation-configs.show');
        Route::put('valuation-configs/{config}', [InventoryValuationController::class, 'update'])
            ->name('inventory.valuation-configs.update');
        Route::delete('valuation-configs/{config}', [InventoryValuationController::class, 'destroy'])
            ->name('inventory.valuation-configs.destroy');

        // Batch management
        Route::get('batches', [InventoryBatchController::class, 'index'])
            ->name('inventory.batches.index');
        Route::post('batches', [InventoryBatchController::class, 'store'])
            ->name('inventory.batches.store');
        Route::get('batches/{batch}', [InventoryBatchController::class, 'show'])
            ->name('inventory.batches.show');
        Route::put('batches/{batch}', [InventoryBatchController::class, 'update'])
            ->name('inventory.batches.update');
        Route::delete('batches/{batch}', [InventoryBatchController::class, 'destroy'])
            ->name('inventory.batches.destroy');
    });
