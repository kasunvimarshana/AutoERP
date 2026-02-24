<?php

use Illuminate\Support\Facades\Route;
use Modules\Currency\Presentation\Controllers\CurrencyController;
use Modules\Currency\Presentation\Controllers\ExchangeRateController;

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::get('currencies', [CurrencyController::class, 'index']);
    Route::get('currencies/active', [CurrencyController::class, 'active']);
    Route::post('currencies', [CurrencyController::class, 'store']);
    Route::get('currencies/{id}', [CurrencyController::class, 'show']);
    Route::put('currencies/{id}', [CurrencyController::class, 'update']);
    Route::delete('currencies/{id}', [CurrencyController::class, 'destroy']);
    Route::post('currencies/{id}/deactivate', [CurrencyController::class, 'deactivate']);

    Route::get('exchange-rates', [ExchangeRateController::class, 'index']);
    Route::post('exchange-rates', [ExchangeRateController::class, 'store']);
    Route::get('exchange-rates/{id}', [ExchangeRateController::class, 'show']);
    Route::delete('exchange-rates/{id}', [ExchangeRateController::class, 'destroy']);
});
