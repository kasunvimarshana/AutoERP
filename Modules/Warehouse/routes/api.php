<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Warehouse\Interfaces\Http\Controllers\WarehouseController;

/*
|--------------------------------------------------------------------------
| Warehouse Module API Routes
|--------------------------------------------------------------------------
|
| All routes are versioned under /api/v1/warehouse
|
*/

Route::middleware('auth:api')->prefix('api/v1')->name('warehouse.')->group(function (): void {
    Route::get('warehouse/picking-orders', [WarehouseController::class, 'listPickingOrders'])->name('picking-orders.index');
    Route::post('warehouse/picking-orders', [WarehouseController::class, 'createPickingOrder'])->name('picking-orders.store');
    Route::get('warehouse/picking-orders/{id}', [WarehouseController::class, 'showPickingOrder'])->name('picking-orders.show');
    Route::post('warehouse/picking-orders/{id}/complete', [WarehouseController::class, 'completePickingOrder'])->name('picking-orders.complete');
    Route::get('warehouse/putaway/{productId}/{warehouseId}', [WarehouseController::class, 'getPutawayRecommendation'])->name('putaway.show');
});
