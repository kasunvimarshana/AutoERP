<?php
use Illuminate\Support\Facades\Route;
use Modules\Warehouse\Infrastructure\Http\Controllers\WarehouseController;
use Modules\Warehouse\Infrastructure\Http\Controllers\WarehouseZoneController;
use Modules\Warehouse\Infrastructure\Http\Controllers\WarehouseLocationController;

Route::prefix('warehouses')->group(function () {
    Route::get('/',        [WarehouseController::class, 'index']);
    Route::post('/',       [WarehouseController::class, 'store']);
    Route::get('/{id}',    [WarehouseController::class, 'show']);
    Route::patch('/{id}',  [WarehouseController::class, 'update']);
    Route::delete('/{id}', [WarehouseController::class, 'destroy']);

    Route::get('/{warehouseId}/zones',  [WarehouseZoneController::class, 'index']);
    Route::post('/{warehouseId}/zones', [WarehouseZoneController::class, 'store']);
});

Route::prefix('warehouse-zones')->group(function () {
    Route::get('/{id}',    [WarehouseZoneController::class, 'show']);
    Route::patch('/{id}',  [WarehouseZoneController::class, 'update']);
    Route::delete('/{id}', [WarehouseZoneController::class, 'destroy']);

    Route::get('/{zoneId}/locations',  [WarehouseLocationController::class, 'index']);
    Route::post('/{zoneId}/locations', [WarehouseLocationController::class, 'store']);
});

Route::prefix('warehouse-locations')->group(function () {
    Route::get('/{id}',    [WarehouseLocationController::class, 'show']);
    Route::patch('/{id}',  [WarehouseLocationController::class, 'update']);
    Route::delete('/{id}', [WarehouseLocationController::class, 'destroy']);
});
