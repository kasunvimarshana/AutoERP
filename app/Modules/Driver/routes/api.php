<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Driver\Infrastructure\Http\Controllers\DriverController;
use Modules\Driver\Infrastructure\Http\Controllers\DriverLicenseController;

Route::middleware(['auth.configured', 'resolve.tenant'])->group(function (): void {

    // Available drivers (before resource routes to avoid ID collision)
    Route::get('/drivers/available', [DriverController::class, 'available']);

    // Expiring-soon licenses
    Route::get('/driver-licenses/expiring-soon', [DriverLicenseController::class, 'expiringSoon']);

    // Driver CRUD + status change
    Route::prefix('drivers')->group(function (): void {
        Route::get('/', [DriverController::class, 'index']);
        Route::post('/', [DriverController::class, 'store']);
        Route::get('/{id}', [DriverController::class, 'show']);
        Route::put('/{id}', [DriverController::class, 'update']);
        Route::delete('/{id}', [DriverController::class, 'destroy']);
        Route::patch('/{id}/status', [DriverController::class, 'changeStatus']);

        // Nested licenses
        Route::prefix('/{driverId}/licenses')->group(function (): void {
            Route::get('/', [DriverLicenseController::class, 'index']);
            Route::post('/', [DriverLicenseController::class, 'store']);
            Route::get('/{id}', [DriverLicenseController::class, 'show']);
            Route::put('/{id}', [DriverLicenseController::class, 'update']);
            Route::delete('/{id}', [DriverLicenseController::class, 'destroy']);
        });
    });
});
