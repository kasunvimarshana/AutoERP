<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Vehicle\Presentation\Http\Controllers\VehicleController;
use Modules\Vehicle\Presentation\Http\Controllers\VehicleDocumentController;

Route::prefix('api/vehicle')
    ->middleware('api')
    ->name('vehicle.')
    ->group(function (): void {
        Route::prefix('tenants/{tenant}')
            ->name('tenants.')
            ->group(function (): void {
                Route::apiResource('vehicles', VehicleController::class);

                Route::prefix('vehicles/{vehicle}')
                    ->name('vehicles.')
                    ->group(function (): void {
                        Route::apiResource('documents', VehicleDocumentController::class);
                    });
            });
    });
