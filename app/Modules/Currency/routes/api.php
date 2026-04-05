<?php
declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    Route::prefix('currencies')->group(function () {
        Route::get('/',          [\Modules\Currency\Infrastructure\Http\Controllers\CurrencyController::class, 'index']);
        Route::post('/',         [\Modules\Currency\Infrastructure\Http\Controllers\CurrencyController::class, 'store']);
        Route::get('/{code}',    [\Modules\Currency\Infrastructure\Http\Controllers\CurrencyController::class, 'show']);
        Route::put('/{code}',    [\Modules\Currency\Infrastructure\Http\Controllers\CurrencyController::class, 'update']);
    });
    Route::prefix('exchange-rates')->group(function () {
        Route::get('/',          [\Modules\Currency\Infrastructure\Http\Controllers\ExchangeRateController::class, 'index']);
        Route::post('/',         [\Modules\Currency\Infrastructure\Http\Controllers\ExchangeRateController::class, 'store']);
        Route::put('/{id}',      [\Modules\Currency\Infrastructure\Http\Controllers\ExchangeRateController::class, 'update']);
        Route::delete('/{id}',   [\Modules\Currency\Infrastructure\Http\Controllers\ExchangeRateController::class, 'destroy']);
        Route::post('/convert',  [\Modules\Currency\Infrastructure\Http\Controllers\ExchangeRateController::class, 'convert']);
    });
});
