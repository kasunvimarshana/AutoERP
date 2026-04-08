<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Currency\Infrastructure\Http\Controllers\CurrencyController;
use Modules\Currency\Infrastructure\Http\Controllers\ExchangeRateController;

Route::prefix('api')->middleware(['auth:api'])->group(function (): void {
    Route::apiResource('currencies', CurrencyController::class);
    Route::apiResource('exchange-rates', ExchangeRateController::class);
    Route::post('exchange-rates/convert', [ExchangeRateController::class, 'convert']);
});
