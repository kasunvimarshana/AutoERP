<?php
use Illuminate\Support\Facades\Route;
use Modules\Pricing\Infrastructure\Http\Controllers\PriceListController;
use Modules\Pricing\Infrastructure\Http\Controllers\TaxRateController;
Route::prefix('api')->group(function () {
    Route::apiResource('price-lists', PriceListController::class);
    Route::apiResource('tax-rates', TaxRateController::class);
});
