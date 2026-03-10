<?php

declare(strict_types=1);

use App\Http\Controllers\HealthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Product Service
|--------------------------------------------------------------------------
*/

Route::get('/health', HealthController::class)->name('health');

Route::middleware(['resolve.tenant'])->group(function (): void {

    Route::middleware('auth:api')->group(function (): void {

        // Products
        Route::prefix('products')->name('products.')->group(function (): void {
            Route::get('/',          [ProductController::class, 'index'])->name('index');
            Route::get('/search',    [ProductController::class, 'search'])->name('search');
            Route::post('/',         [ProductController::class, 'store'])->name('store');
            Route::get('/{id}',      [ProductController::class, 'show'])->name('show');
            Route::put('/{id}',      [ProductController::class, 'update'])->name('update');
            Route::patch('/{id}',    [ProductController::class, 'update'])->name('patch');
            Route::delete('/{id}',   [ProductController::class, 'destroy'])->name('destroy');
        });

        // Categories
        Route::prefix('categories')->name('categories.')->group(function (): void {
            Route::get('/',        [CategoryController::class, 'index'])->name('index');
            Route::post('/',       [CategoryController::class, 'store'])->name('store');
            Route::get('/{id}',    [CategoryController::class, 'show'])->name('show');
            Route::put('/{id}',    [CategoryController::class, 'update'])->name('update');
            Route::delete('/{id}', [CategoryController::class, 'destroy'])->name('destroy');
        });
    });
});
