<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\ReorderController;
use Modules\Inventory\Http\Controllers\StockCountController;
use Modules\Inventory\Http\Controllers\StockItemController;
use Modules\Inventory\Http\Controllers\StockMovementController;
use Modules\Inventory\Http\Controllers\WarehouseController;

/*
|--------------------------------------------------------------------------
| Inventory API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/v1')->middleware(['api', 'jwt.auth', 'tenant'])->group(function () {

    // Warehouses
    Route::apiResource('warehouses', WarehouseController::class);
    Route::post('warehouses/{warehouse}/activate', [WarehouseController::class, 'activate']);
    Route::post('warehouses/{warehouse}/deactivate', [WarehouseController::class, 'deactivate']);
    Route::post('warehouses/{warehouse}/set-default', [WarehouseController::class, 'setDefault']);

    // Stock Items
    Route::get('stock-items', [StockItemController::class, 'index']);
    Route::get('stock-items/{stockItem}', [StockItemController::class, 'show']);
    Route::get('stock-items/by-product-warehouse', [StockItemController::class, 'getByProductAndWarehouse']);
    Route::get('stock-items/reports/low-stock', [StockItemController::class, 'lowStock']);
    Route::get('stock-items/reports/valuation', [StockItemController::class, 'valuationReport']);
    Route::get('stock-items/reports/product-valuation', [StockItemController::class, 'productValuation']);

    // Stock Movements
    Route::get('stock-movements', [StockMovementController::class, 'index']);
    Route::get('stock-movements/{stockMovement}', [StockMovementController::class, 'show']);
    Route::post('stock-movements/receive', [StockMovementController::class, 'receive']);
    Route::post('stock-movements/issue', [StockMovementController::class, 'issue']);
    Route::post('stock-movements/transfer', [StockMovementController::class, 'transfer']);
    Route::post('stock-movements/adjust', [StockMovementController::class, 'adjust']);

    // Stock Counts
    Route::apiResource('stock-counts', StockCountController::class);
    Route::post('stock-counts/{stockCount}/start', [StockCountController::class, 'start']);
    Route::post('stock-counts/{stockCount}/update-items', [StockCountController::class, 'updateItems']);
    Route::post('stock-counts/{stockCount}/complete', [StockCountController::class, 'complete']);
    Route::post('stock-counts/{stockCount}/reconcile', [StockCountController::class, 'reconcile']);
    Route::post('stock-counts/{stockCount}/cancel', [StockCountController::class, 'cancel']);

    // Reorder & Stock Analysis
    Route::get('reorder/suggestions', [ReorderController::class, 'suggestions']);
    Route::get('reorder/analyze-product', [ReorderController::class, 'analyzeProduct']);
    Route::get('reorder/stock-health', [ReorderController::class, 'stockHealth']);
    Route::get('reorder/check-reorder', [ReorderController::class, 'checkReorder']);
});
