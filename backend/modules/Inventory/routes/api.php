<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\CategoryController;
use Modules\Inventory\Http\Controllers\ProductController;
use Modules\Inventory\Http\Controllers\StockController;
use Modules\Inventory\Http\Controllers\WarehouseController;
use Modules\Inventory\Http\Controllers\BatchController;
use Modules\Inventory\Http\Controllers\SerialNumberController;

/*
|--------------------------------------------------------------------------
| Inventory Module API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the Inventory module.
| These routes are loaded by the RouteServiceProvider and are
| assigned to the "api" middleware group.
|
*/

Route::middleware(['auth:sanctum', 'tenant'])->prefix('inventory')->name('inventory.')->group(function () {

    // Category Routes
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::get('/tree', [CategoryController::class, 'tree'])->name('tree');
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::get('/{id}', [CategoryController::class, 'show'])->name('show');
        Route::put('/{id}', [CategoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [CategoryController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/children', [CategoryController::class, 'children'])->name('children');
        Route::post('/{id}/activate', [CategoryController::class, 'activate'])->name('activate');
        Route::post('/{id}/deactivate', [CategoryController::class, 'deactivate'])->name('deactivate');
    });

    // Product Routes
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::post('/', [ProductController::class, 'store'])->name('store');
        Route::get('/sku', [ProductController::class, 'getBySKU'])->name('by-sku');
        Route::get('/low-stock', [ProductController::class, 'lowStock'])->name('low-stock');
        Route::post('/bulk-import', [ProductController::class, 'bulkImport'])->name('bulk-import');
        Route::get('/{product}', [ProductController::class, 'show'])->name('show');
        Route::put('/{product}', [ProductController::class, 'update'])->name('update');
        Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');
    });

    // Stock Management Routes
    Route::prefix('stock')->name('stock.')->group(function () {
        Route::get('/level', [StockController::class, 'level'])->name('level');
        Route::get('/valuation', [StockController::class, 'valuation'])->name('valuation');
        Route::get('/{product}/movements', [StockController::class, 'movements'])->name('movements');
        Route::get('/{product}/total', [StockController::class, 'totalStock'])->name('total');

        // Stock Operations
        Route::post('/transaction', [StockController::class, 'transaction'])->name('transaction');
        Route::post('/adjust', [StockController::class, 'adjust'])->name('adjust');
        Route::post('/reserve', [StockController::class, 'reserve'])->name('reserve');
        Route::post('/allocate', [StockController::class, 'allocate'])->name('allocate');
        Route::post('/release', [StockController::class, 'release'])->name('release');
    });

    // Warehouse Routes
    Route::prefix('warehouses')->name('warehouses.')->group(function () {
        Route::get('/', [WarehouseController::class, 'index'])->name('index');
        Route::post('/', [WarehouseController::class, 'store'])->name('store');
        Route::get('/{warehouse}', [WarehouseController::class, 'show'])->name('show');
        Route::put('/{warehouse}', [WarehouseController::class, 'update'])->name('update');
        Route::delete('/{warehouse}', [WarehouseController::class, 'destroy'])->name('destroy');
        Route::get('/{warehouse}/stock-summary', [WarehouseController::class, 'stockSummary'])->name('stock-summary');
    });

    // Batch Tracking Routes
    Route::prefix('batches')->name('batches.')->group(function () {
        Route::get('/', [BatchController::class, 'index'])->name('index');
        Route::post('/', [BatchController::class, 'store'])->name('store');
        Route::get('/expired', [BatchController::class, 'expired'])->name('expired');
        Route::get('/{id}', [BatchController::class, 'show'])->name('show');
        Route::put('/{id}', [BatchController::class, 'update'])->name('update');
    });

    // Product-specific batch routes
    Route::get('/products/{productId}/batches/available', [BatchController::class, 'available'])
        ->name('products.batches.available');

    // Serial Number Tracking Routes
    Route::prefix('serial-numbers')->name('serial-numbers.')->group(function () {
        Route::get('/', [SerialNumberController::class, 'index'])->name('index');
        Route::post('/', [SerialNumberController::class, 'store'])->name('store');
        Route::post('/bulk', [SerialNumberController::class, 'bulkStore'])->name('bulk-store');
        Route::get('/{id}', [SerialNumberController::class, 'show'])->name('show');
        Route::post('/{id}/allocate', [SerialNumberController::class, 'allocate'])->name('allocate');
        Route::post('/{id}/return', [SerialNumberController::class, 'return'])->name('return');
        Route::get('/{serialNumber}/warranty', [SerialNumberController::class, 'warranty'])->name('warranty');
    });

    // Product-specific serial number routes
    Route::get('/products/{productId}/serial-numbers/available', [SerialNumberController::class, 'available'])
        ->name('products.serial-numbers.available');
});
