<?php

use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Product Service
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Health Check (public — no auth required)
    |--------------------------------------------------------------------------
    */
    Route::get('/health', HealthController::class)->name('health');

    /*
    |--------------------------------------------------------------------------
    | Authenticated + Tenant-scoped Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['keycloak', 'tenant'])->group(function () {

        /*
        |----------------------------------------------------------------------
        | Products
        |----------------------------------------------------------------------
        */
        Route::prefix('products')->group(function () {
            Route::get('/',      [ProductController::class, 'index'])
                ->name('products.index');

            Route::get('/{id}',  [ProductController::class, 'show'])
                ->name('products.show')
                ->whereNumber('id');

            Route::post('/', [ProductController::class, 'store'])
                ->middleware('rbac:products.write,admin')
                ->name('products.store');

            Route::match(['put', 'patch'], '/{id}', [ProductController::class, 'update'])
                ->middleware('rbac:products.write,admin')
                ->name('products.update')
                ->whereNumber('id');

            Route::delete('/{id}', [ProductController::class, 'destroy'])
                ->middleware('rbac:products.delete,admin')
                ->name('products.destroy')
                ->whereNumber('id');
        });

        /*
        |----------------------------------------------------------------------
        | Categories
        |----------------------------------------------------------------------
        */
        Route::prefix('categories')->group(function () {
            Route::get('/',      [CategoryController::class, 'index'])
                ->name('categories.index');

            Route::get('/{id}',  [CategoryController::class, 'show'])
                ->name('categories.show')
                ->whereNumber('id');

            Route::post('/', [CategoryController::class, 'store'])
                ->middleware('rbac:categories.write,admin')
                ->name('categories.store');

            Route::match(['put', 'patch'], '/{id}', [CategoryController::class, 'update'])
                ->middleware('rbac:categories.write,admin')
                ->name('categories.update')
                ->whereNumber('id');

            Route::delete('/{id}', [CategoryController::class, 'destroy'])
                ->middleware('rbac:categories.delete,admin')
                ->name('categories.destroy')
                ->whereNumber('id');
        });
    });
});
