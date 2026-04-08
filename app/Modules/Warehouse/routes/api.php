<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Warehouse\Infrastructure\Http\Controllers\WarehouseController;
use Modules\Warehouse\Infrastructure\Http\Controllers\WarehouseLocationController;

Route::prefix('api')->middleware(['auth:api'])->group(function (): void {
    Route::get('warehouses/{id}/locations/tree', [WarehouseController::class, 'tree']);
    Route::apiResource('warehouses', WarehouseController::class);
    Route::apiResource('warehouse-locations', WarehouseLocationController::class);
});
