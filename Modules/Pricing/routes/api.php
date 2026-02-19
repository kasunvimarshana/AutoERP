<?php

use Illuminate\Support\Facades\Route;
use Modules\Pricing\Http\Controllers\DiscountRuleController;
use Modules\Pricing\Http\Controllers\PriceListController;
use Modules\Pricing\Http\Controllers\PricingController;
use Modules\Pricing\Http\Controllers\TaxRateController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Pricing calculation endpoints
    Route::post('pricing/calculate', [PricingController::class, 'calculate'])->name('pricing.calculate');
    Route::post('pricing/calculate-cart', [PricingController::class, 'calculateCart'])->name('pricing.calculate-cart');
    Route::get('pricing/strategies', [PricingController::class, 'strategies'])->name('pricing.strategies');

    // Price lists
    Route::apiResource('price-lists', PriceListController::class)->names('price-lists');

    // Discount rules
    Route::apiResource('discount-rules', DiscountRuleController::class)->names('discount-rules');

    // Tax rates
    Route::post('tax-rates/calculate', [TaxRateController::class, 'calculate'])->name('tax-rates.calculate');
    Route::apiResource('tax-rates', TaxRateController::class)->names('tax-rates');
});
