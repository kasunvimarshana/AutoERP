<?php

namespace App\Modules\Product\Routes;

use App\Modules\Product\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

class ProductRoutes
{
    public static function register(): void
    {
        Route::middleware(['keycloak'])->group(function () {
            Route::get('/products', [ProductController::class, 'index']);
            Route::post('/products', [ProductController::class, 'store'])
                ->middleware('keycloak:admin,manager');
            Route::get('/products/categories', [ProductController::class, 'categories']);
            Route::get('/products/{id}', [ProductController::class, 'show']);
            Route::put('/products/{id}', [ProductController::class, 'update'])
                ->middleware('keycloak:admin,manager');
            Route::patch('/products/{id}', [ProductController::class, 'update'])
                ->middleware('keycloak:admin,manager');
            Route::delete('/products/{id}', [ProductController::class, 'destroy'])
                ->middleware('keycloak:admin');
        });
    }
}
