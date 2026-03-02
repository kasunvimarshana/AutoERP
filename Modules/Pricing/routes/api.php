<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Pricing\Interfaces\Http\Controllers\PricingController;

/*
|--------------------------------------------------------------------------
| Pricing Module API Routes
|--------------------------------------------------------------------------
|
| All routes are versioned under /api/v1/pricing
|
*/

Route::middleware('auth:api')->prefix('api/v1')->name('pricing.')->group(function (): void {
    Route::post('pricing/calculate', [PricingController::class, 'calculatePrice'])->name('calculate');
    Route::get('pricing/lists', [PricingController::class, 'listPriceLists'])->name('lists.index');
    Route::post('pricing/lists', [PricingController::class, 'createPriceList'])->name('lists.store');
    Route::get('pricing/lists/{id}', [PricingController::class, 'showPriceList'])->name('lists.show');
    Route::put('pricing/lists/{id}', [PricingController::class, 'updatePriceList'])->name('lists.update');
    Route::delete('pricing/lists/{id}', [PricingController::class, 'deletePriceList'])->name('lists.destroy');
    Route::get('pricing/discount-rules', [PricingController::class, 'listDiscountRules'])->name('discount-rules.index');
    Route::post('pricing/discount-rules', [PricingController::class, 'createDiscountRule'])->name('discount-rules.store');
    Route::get('pricing/discount-rules/{id}', [PricingController::class, 'showDiscountRule'])->name('discount-rules.show');
    Route::put('pricing/discount-rules/{id}', [PricingController::class, 'updateDiscountRule'])->name('discount-rules.update');
    Route::delete('pricing/discount-rules/{id}', [PricingController::class, 'deleteDiscountRule'])->name('discount-rules.destroy');
    Route::get('products/{productId}/prices', [PricingController::class, 'listProductPrices'])->name('product-prices.index');
    Route::post('products/{productId}/prices', [PricingController::class, 'createProductPrice'])->name('product-prices.store');
});
