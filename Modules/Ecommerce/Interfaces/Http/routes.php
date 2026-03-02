<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Interfaces\Http\Controllers\CartController;
use Modules\Ecommerce\Interfaces\Http\Controllers\StorefrontOrderController;
use Modules\Ecommerce\Interfaces\Http\Controllers\StorefrontProductController;

Route::prefix('api/v1/ecommerce')->group(function (): void {
    // Storefront Products
    Route::get('/products', [StorefrontProductController::class, 'index']);
    Route::post('/products', [StorefrontProductController::class, 'store']);
    Route::get('/products/featured', [StorefrontProductController::class, 'featured']);
    Route::get('/products/{id}', [StorefrontProductController::class, 'show']);
    Route::put('/products/{id}', [StorefrontProductController::class, 'update']);
    Route::delete('/products/{id}', [StorefrontProductController::class, 'destroy']);

    // Shopping Carts
    Route::post('/carts', [CartController::class, 'store']);
    Route::get('/carts/{token}', [CartController::class, 'show']);
    Route::post('/carts/{token}/items', [CartController::class, 'addItem']);
    Route::delete('/carts/{token}/items/{itemId}', [CartController::class, 'removeItem']);
    Route::post('/carts/{token}/checkout', [CartController::class, 'checkout']);

    // Storefront Orders
    Route::get('/orders', [StorefrontOrderController::class, 'index']);
    Route::get('/orders/{id}', [StorefrontOrderController::class, 'show']);
    Route::put('/orders/{id}/status', [StorefrontOrderController::class, 'updateStatus']);
    Route::post('/orders/{id}/cancel', [StorefrontOrderController::class, 'cancel']);
    Route::get('/orders/{id}/lines', [StorefrontOrderController::class, 'lines']);
    Route::delete('/orders/{id}', [StorefrontOrderController::class, 'destroy']);
});
