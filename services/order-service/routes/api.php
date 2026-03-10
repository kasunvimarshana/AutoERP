<?php

declare(strict_types=1);

use App\Http\Controllers\HealthController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Order Service
|--------------------------------------------------------------------------
*/

Route::get('/health', HealthController::class)->name('health');

Route::middleware(['resolve.tenant'])->group(function (): void {

    Route::middleware('auth:api')->group(function (): void {

        Route::prefix('orders')->name('orders.')->group(function (): void {
            Route::get('/',                    [OrderController::class, 'index'])->name('index');
            Route::post('/',                   [OrderController::class, 'store'])->name('store');
            Route::get('/{id}',                [OrderController::class, 'show'])->name('show');
            Route::get('/saga/{sagaId}/status',[OrderController::class, 'sagaStatus'])->name('sagaStatus');
        });
    });
});
