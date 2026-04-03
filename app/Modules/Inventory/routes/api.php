<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Infrastructure\Http\Controllers\InventoryBatchController;
use Modules\Inventory\Infrastructure\Http\Controllers\InventoryCycleCountController;
use Modules\Inventory\Infrastructure\Http\Controllers\InventoryCycleCountLineController;
use Modules\Inventory\Infrastructure\Http\Controllers\InventoryLevelController;
use Modules\Inventory\Infrastructure\Http\Controllers\InventoryLocationController;
use Modules\Inventory\Infrastructure\Http\Controllers\InventorySerialNumberController;
use Modules\Inventory\Infrastructure\Http\Controllers\InventorySettingController;
use Modules\Inventory\Infrastructure\Http\Controllers\InventoryValuationLayerController;

Route::prefix('inventory')->group(function () {
    // Settings (singleton per tenant)
    Route::get('settings', [InventorySettingController::class, 'show']);
    Route::post('settings', [InventorySettingController::class, 'store']);
    Route::put('settings/{id}', [InventorySettingController::class, 'update']);

    // Locations
    Route::apiResource('locations', InventoryLocationController::class);

    // Batches
    Route::apiResource('batches', InventoryBatchController::class);

    // Serial numbers
    Route::apiResource('serial-numbers', InventorySerialNumberController::class);

    // Levels
    Route::apiResource('levels', InventoryLevelController::class);
    Route::post('levels/{id}/reserve', [InventoryLevelController::class, 'reserve']);
    Route::post('levels/{id}/release', [InventoryLevelController::class, 'release']);
    Route::post('levels/{id}/adjust', [InventoryLevelController::class, 'adjust']);

    // Valuation layers (read + create only; consumed by internal services)
    Route::get('valuation-layers', [InventoryValuationLayerController::class, 'index']);
    Route::post('valuation-layers', [InventoryValuationLayerController::class, 'store']);
    Route::get('valuation-layers/{id}', [InventoryValuationLayerController::class, 'show']);

    // Cycle counts
    Route::apiResource('cycle-counts', InventoryCycleCountController::class);
    Route::post('cycle-counts/{id}/reconcile', [InventoryCycleCountController::class, 'reconcile']);

    // Cycle count lines
    Route::apiResource('cycle-count-lines', InventoryCycleCountLineController::class);
});
