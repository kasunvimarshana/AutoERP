<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Product\Interfaces\Http\Controllers\ProductController;
use Modules\Product\Interfaces\Http\Controllers\UomController;

/*
|--------------------------------------------------------------------------
| Product Module API Routes
|--------------------------------------------------------------------------
|
| All routes are versioned under /api/v1/products and /api/v1/uoms
|
*/

Route::middleware('auth:api')->prefix('api/v1')->group(function (): void {
    Route::name('products.')->group(function (): void {
        Route::apiResource('products', ProductController::class);
    });

    Route::name('uoms.')->group(function (): void {
        Route::apiResource('uoms', UomController::class);
        Route::get('products/{productId}/uom-conversions', [UomController::class, 'listConversions'])
            ->name('products.uom-conversions.index');
        Route::post('products/{productId}/uom-conversions', [UomController::class, 'storeConversion'])
            ->name('products.uom-conversions.store');
        Route::get('uom-conversions/{id}', [UomController::class, 'showConversion'])
            ->name('uom-conversions.show');
        Route::delete('uom-conversions/{id}', [UomController::class, 'destroyConversion'])
            ->name('uom-conversions.destroy');
    });
});
