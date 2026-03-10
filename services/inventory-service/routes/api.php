<?php

declare(strict_types=1);

use App\Http\Controllers\HealthController;
use App\Http\Controllers\InventoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Inventory Service
|--------------------------------------------------------------------------
*/

Route::get('/health', HealthController::class)->name('health');

Route::middleware(['resolve.tenant'])->group(function (): void {

    // Authenticated routes
    Route::middleware('auth:api')->group(function (): void {

        Route::prefix('inventory')->name('inventory.')->group(function (): void {
            Route::get('/',                           [InventoryController::class, 'index'])->name('index');
            Route::get('/product/{productId}',        [InventoryController::class, 'showByProduct'])->name('showByProduct');
            Route::post('/adjust',                    [InventoryController::class, 'adjustStock'])->name('adjustStock');
            Route::post('/reserve',                   [InventoryController::class, 'reserve'])->name('reserve');
            Route::delete('/reserve/{reservationId}', [InventoryController::class, 'releaseReservation'])->name('releaseReservation');
        });
    });
});
