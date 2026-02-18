<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\Api\ProductCategoryController;
use Modules\Inventory\Http\Controllers\Api\ProductController;
use Modules\Inventory\Http\Controllers\Api\StockLevelController;
use Modules\Inventory\Http\Controllers\Api\StockLocationController;
use Modules\Inventory\Http\Controllers\Api\StockMovementController;
use Modules\Inventory\Http\Controllers\Api\UnitOfMeasureController;
use Modules\Inventory\Http\Controllers\Api\WarehouseController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the Inventory module.
|
*/

Route::prefix('v1')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    // Product routes
    Route::get('products/search', [ProductController::class, 'search'])->name('products.search');
    Route::get('products/by-category/{categoryId}', [ProductController::class, 'byCategory'])->name('products.by-category');
    Route::apiResource('products', ProductController::class);

    // Product Category routes
    Route::get('product-categories/tree', [ProductCategoryController::class, 'tree'])->name('product-categories.tree');
    Route::apiResource('product-categories', ProductCategoryController::class);

    // Warehouse routes
    Route::get('warehouses/{id}/stock-summary', [WarehouseController::class, 'stockSummary'])->name('warehouses.stock-summary');
    Route::apiResource('warehouses', WarehouseController::class);

    // Stock Location routes
    Route::get('stock-locations/by-warehouse/{warehouseId}', [StockLocationController::class, 'byWarehouse'])->name('stock-locations.by-warehouse');
    Route::apiResource('stock-locations', StockLocationController::class);

    // Stock Level routes
    Route::get('stock-levels', [StockLevelController::class, 'index'])->name('stock-levels.index');
    Route::post('stock-levels/adjust', [StockLevelController::class, 'adjust'])->name('stock-levels.adjust');

    // Stock Movement routes
    Route::post('stock-movements/receive', [StockMovementController::class, 'receive'])->name('stock-movements.receive');
    Route::post('stock-movements/ship', [StockMovementController::class, 'ship'])->name('stock-movements.ship');
    Route::post('stock-movements/transfer', [StockMovementController::class, 'transfer'])->name('stock-movements.transfer');
    Route::get('stock-movements/history/{productId}', [StockMovementController::class, 'history'])->name('stock-movements.history');
    Route::get('stock-movements/{id}', [StockMovementController::class, 'show'])->name('stock-movements.show');
    Route::get('stock-movements', [StockMovementController::class, 'index'])->name('stock-movements.index');

    // Unit of Measure routes
    Route::get('unit-of-measures/active', [UnitOfMeasureController::class, 'active'])->name('unit-of-measures.active');
    Route::get('unit-of-measures/base-units', [UnitOfMeasureController::class, 'baseUnits'])->name('unit-of-measures.base-units');
    Route::post('unit-of-measures/convert', [UnitOfMeasureController::class, 'convert'])->name('unit-of-measures.convert');
    Route::apiResource('unit-of-measures', UnitOfMeasureController::class);
});
