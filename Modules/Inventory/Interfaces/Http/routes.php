<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Interfaces\Http\Controllers\InventoryController;
use Modules\Inventory\Interfaces\Http\Controllers\LotController;
use Modules\Inventory\Interfaces\Http\Controllers\ReorderRuleController;
use Modules\Inventory\Interfaces\Http\Controllers\WarehouseController;

Route::prefix('api/v1')->group(function (): void {
    // Warehouse routes
    Route::get('/warehouses', [WarehouseController::class, 'index']);
    Route::post('/warehouses', [WarehouseController::class, 'store']);
    Route::get('/warehouses/{id}', [WarehouseController::class, 'show']);

    // Inventory transaction routes
    Route::post('/inventory/receive', [InventoryController::class, 'receive']);
    Route::post('/inventory/adjust', [InventoryController::class, 'adjust']);
    Route::post('/inventory/transfer', [InventoryController::class, 'transfer']);
    Route::post('/inventory/ship', [InventoryController::class, 'ship']);
    Route::post('/inventory/reserve-stock', [InventoryController::class, 'reserveStock']);
    Route::post('/inventory/release-stock', [InventoryController::class, 'releaseStock']);
    Route::post('/inventory/return', [InventoryController::class, 'returnStock']);

    // Inventory read routes
    Route::get('/inventory/stock', [InventoryController::class, 'stock']);
    Route::get('/inventory/ledger', [InventoryController::class, 'ledger']);

    // Inventory lot/batch/serial routes
    Route::get('/inventory/lots', [LotController::class, 'index']);
    Route::post('/inventory/lots', [LotController::class, 'store']);
    Route::get('/inventory/lots/{id}', [LotController::class, 'show']);
    Route::put('/inventory/lots/{id}', [LotController::class, 'update']);
    Route::delete('/inventory/lots/{id}', [LotController::class, 'destroy']);

    // Reorder rule routes
    Route::get('/inventory/reorder-rules', [ReorderRuleController::class, 'index']);
    Route::post('/inventory/reorder-rules', [ReorderRuleController::class, 'store']);
    Route::get('/inventory/reorder-rules/{id}', [ReorderRuleController::class, 'show']);
    Route::put('/inventory/reorder-rules/{id}', [ReorderRuleController::class, 'update']);
    Route::delete('/inventory/reorder-rules/{id}', [ReorderRuleController::class, 'destroy']);

    // Low-stock alerts
    Route::get('/inventory/low-stock', [ReorderRuleController::class, 'lowStock']);

    // ABC analysis
    Route::get('/inventory/abc-analysis', [InventoryController::class, 'abcAnalysis']);

    // Barcode / RFID scan lookup
    Route::get('/inventory/scan', [InventoryController::class, 'scan']);

    // Inventory valuation report
    Route::get('/inventory/valuation', [InventoryController::class, 'valuation']);

    // Demand forecast (historical outflow-based)
    Route::get('/inventory/demand-forecast', [InventoryController::class, 'demandForecast']);

    // Inventory turnover rate
    Route::get('/inventory/turnover', [InventoryController::class, 'turnover']);

    // Carrying cost report
    Route::get('/inventory/carrying-costs', [InventoryController::class, 'carryingCosts']);
});
