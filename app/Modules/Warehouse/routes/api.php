<?php
use Illuminate\Support\Facades\Route;
use Modules\Warehouse\Infrastructure\Http\Controllers\WarehouseController;
use Modules\Warehouse\Infrastructure\Http\Controllers\WarehouseLocationController;
Route::prefix('api')->group(function () {
    Route::apiResource('warehouses', WarehouseController::class);
    Route::get('warehouses/{warehouseId}/locations', [WarehouseLocationController::class, 'index']);
    Route::get('warehouses/{warehouseId}/locations/tree', [WarehouseLocationController::class, 'tree']);
    Route::post('warehouse-locations', [WarehouseLocationController::class, 'store']);
    Route::get('warehouse-locations/{id}', [WarehouseLocationController::class, 'show']);
    Route::put('warehouse-locations/{id}', [WarehouseLocationController::class, 'update']);
    Route::delete('warehouse-locations/{id}', [WarehouseLocationController::class, 'destroy']);
});
