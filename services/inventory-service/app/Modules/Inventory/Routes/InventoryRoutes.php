<?php

namespace App\Modules\Inventory\Routes;

use App\Modules\Inventory\Controllers\InventoryController;
use Illuminate\Support\Facades\Route;

class InventoryRoutes
{
    public static function register(): void
    {
        Route::middleware(['keycloak'])->group(function () {
            Route::get('/inventory', [InventoryController::class, 'index']);
            Route::post('/inventory', [InventoryController::class, 'store'])
                ->middleware('keycloak:admin,manager');
            Route::get('/inventory/{id}', [InventoryController::class, 'show']);
            Route::put('/inventory/{id}', [InventoryController::class, 'update'])
                ->middleware('keycloak:admin,manager');
            Route::delete('/inventory/{id}', [InventoryController::class, 'destroy'])
                ->middleware('keycloak:admin');
            Route::post('/inventory/{id}/adjust-stock', [InventoryController::class, 'adjustStock'])
                ->middleware('keycloak:admin,manager');
            Route::post('/inventory/{id}/reserve', [InventoryController::class, 'reserveStock'])
                ->middleware('keycloak:admin,manager');
            Route::post('/inventory/{id}/release', [InventoryController::class, 'releaseStock'])
                ->middleware('keycloak:admin,manager');
            Route::get('/inventory/product/{productId}', [InventoryController::class, 'showByProduct']);
        });
    }
}
