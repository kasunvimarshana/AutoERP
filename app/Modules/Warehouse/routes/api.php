<?php

use Illuminate\Support\Facades\Route;
use Modules\Warehouse\Infrastructure\Http\Controllers\WarehouseController;
use Modules\Warehouse\Infrastructure\Http\Controllers\WarehouseZoneController;

Route::middleware(['auth:api', 'resolve.tenant'])->group(function () {
    // Static route must be declared BEFORE the resource wildcard route to
    // prevent the {warehouse} segment from swallowing it.
    Route::get('warehouses/by-location/{locationId}', [WarehouseController::class, 'byLocation']);

    Route::apiResource('warehouses', WarehouseController::class);

    Route::apiResource('warehouses.zones', WarehouseZoneController::class);
});
