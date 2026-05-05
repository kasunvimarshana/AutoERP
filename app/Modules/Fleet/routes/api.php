<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Fleet\Infrastructure\Http\Controllers\VehicleController;
use Modules\Fleet\Infrastructure\Http\Controllers\VehicleDocumentController;
use Modules\Fleet\Infrastructure\Http\Controllers\VehicleTypeController;

Route::middleware(['auth.configured', 'resolve.tenant'])->group(function () {
    Route::prefix('vehicle-types')->group(function () {
        Route::get('/', [VehicleTypeController::class, 'index']);
        Route::post('/', [VehicleTypeController::class, 'store']);
        Route::get('/{id}', [VehicleTypeController::class, 'show']);
        Route::put('/{id}', [VehicleTypeController::class, 'update']);
        Route::delete('/{id}', [VehicleTypeController::class, 'destroy']);
    });

    Route::prefix('vehicles')->group(function () {
        Route::get('/available-for-rental', [VehicleController::class, 'availableForRental']);
        Route::get('/available-for-service', [VehicleController::class, 'availableForService']);
        Route::get('/', [VehicleController::class, 'index']);
        Route::post('/', [VehicleController::class, 'store']);
        Route::get('/{id}', [VehicleController::class, 'show']);
        Route::put('/{id}', [VehicleController::class, 'update']);
        Route::delete('/{id}', [VehicleController::class, 'destroy']);
        Route::post('/{id}/state', [VehicleController::class, 'changeState']);

        Route::prefix('/{vehicleId}/documents')->group(function () {
            Route::get('/', [VehicleDocumentController::class, 'index']);
            Route::post('/', [VehicleDocumentController::class, 'store']);
            Route::get('/{id}', [VehicleDocumentController::class, 'show']);
            Route::put('/{id}', [VehicleDocumentController::class, 'update']);
            Route::delete('/{id}', [VehicleDocumentController::class, 'destroy']);
        });
    });

    Route::get('/documents/expiring-soon', [VehicleDocumentController::class, 'expiringSoon']);
});
