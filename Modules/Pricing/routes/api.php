<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Pricing\Http\Controllers\PricingController;

Route::middleware(['api', 'auth:api', 'throttle:api'])->prefix('api')->group(function () {
    Route::post('pricing/calculate', [PricingController::class, 'calculate'])
        ->name('pricing.calculate');

    Route::prefix('products/{product}')->group(function () {
        Route::get('prices', [PricingController::class, 'index'])
            ->name('products.prices.index');

        Route::post('prices', [PricingController::class, 'store'])
            ->name('products.prices.store');

        Route::get('prices/{price}', [PricingController::class, 'show'])
            ->name('products.prices.show');

        Route::put('prices/{price}', [PricingController::class, 'update'])
            ->name('products.prices.update');

        Route::delete('prices/{price}', [PricingController::class, 'destroy'])
            ->name('products.prices.destroy');
    });
});
