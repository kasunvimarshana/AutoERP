<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\{ProductController,CategoryController,WarehouseController,StockController,StockMovementController,HealthController};

Route::get('/health', HealthController::class);

Route::middleware(['tenant', 'auth'])->prefix('v1')->group(function () {
    // Products
    Route::get('/products/low-stock', [ProductController::class, 'getLowStock']);
    Route::get('/products/search',    [ProductController::class, 'search']);
    Route::apiResource('/products',   ProductController::class);

    // Categories
    Route::get('/categories/tree', [CategoryController::class, 'tree']);
    Route::apiResource('/categories', CategoryController::class);

    // Warehouses
    Route::get('/warehouses/{id}/stock', [WarehouseController::class, 'stockSummary']);
    Route::apiResource('/warehouses', WarehouseController::class);

    // Stock
    Route::get('/stock/{productId}/{warehouseId}',       [StockController::class, 'getStockLevel']);
    Route::post('/stock/adjust',                          [StockController::class, 'adjustStock']);
    Route::post('/stock/transfer',                        [StockController::class, 'transferStock']);
    Route::post('/stock/reserve',                         [StockController::class, 'reserveStock']);
    Route::post('/stock/reservations/{id}/commit',        [StockController::class, 'commitReservation']);
    Route::post('/stock/reservations/{id}/release',       [StockController::class, 'releaseReservation']);

    // Stock Movements
    Route::get('/stock-movements',     [StockMovementController::class, 'index']);
    Route::post('/stock-movements',    [StockMovementController::class, 'store']);
    Route::get('/stock-movements/{id}',[StockMovementController::class, 'show']);
});
