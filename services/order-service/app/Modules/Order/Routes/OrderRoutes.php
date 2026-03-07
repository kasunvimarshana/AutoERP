<?php

namespace App\Modules\Order\Routes;

use App\Modules\Order\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

class OrderRoutes
{
    public static function register(): void
    {
        Route::middleware(['keycloak'])->group(function () {
            Route::get('/orders', [OrderController::class, 'index']);
            Route::post('/orders', [OrderController::class, 'store'])
                ->middleware('keycloak:admin,manager');
            Route::get('/orders/{id}', [OrderController::class, 'show']);
            Route::put('/orders/{id}', [OrderController::class, 'update'])
                ->middleware('keycloak:admin,manager');
            Route::patch('/orders/{id}', [OrderController::class, 'update'])
                ->middleware('keycloak:admin,manager');
            Route::patch('/orders/{id}/status', [OrderController::class, 'updateStatus'])
                ->middleware('keycloak:admin,manager');
            Route::patch('/orders/{id}/cancel', [OrderController::class, 'cancel'])
                ->middleware('keycloak:admin,manager');
            Route::delete('/orders/{id}', [OrderController::class, 'destroy'])
                ->middleware('keycloak:admin');
        });
    }
}
