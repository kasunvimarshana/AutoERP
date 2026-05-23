<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Warehouse\Presentation\Http\Controllers\WarehouseController;
use Modules\Warehouse\Presentation\Http\Controllers\WarehouseLocationController;

Route::prefix('api/warehouse')
    ->middleware('api')
    ->name('warehouse.')
    ->group(function (): void {
        Route::prefix('tenants/{tenant}')
            ->name('tenants.')
            ->group(function (): void {
                Route::apiResource('warehouses', WarehouseController::class);

                Route::prefix('warehouses/{warehouse}')
                    ->name('warehouses.')
                    ->group(function (): void {
                        Route::apiResource('locations', WarehouseLocationController::class);
                    });
            });
    });
