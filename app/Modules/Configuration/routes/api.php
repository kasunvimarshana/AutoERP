<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Configuration\Presentation\Http\Controllers\CountryController;
use Modules\Configuration\Presentation\Http\Controllers\CurrencyController;
use Modules\Configuration\Presentation\Http\Controllers\LanguageController;
use Modules\Configuration\Presentation\Http\Controllers\TimezoneController;

Route::prefix('api/configuration')
    ->middleware('api')
    ->name('configuration.')
    ->group(function (): void {
        Route::apiResource('countries', CountryController::class);
        Route::apiResource('currencies', CurrencyController::class);
        Route::apiResource('languages', LanguageController::class);
        Route::apiResource('timezones', TimezoneController::class);
    });
