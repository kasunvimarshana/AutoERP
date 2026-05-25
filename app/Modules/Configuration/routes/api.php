<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Configuration\Presentation\Http\Controllers\ConfigurationController;
use Modules\Configuration\Presentation\Http\Controllers\CountryController;
use Modules\Configuration\Presentation\Http\Controllers\CurrencyController;
use Modules\Configuration\Presentation\Http\Controllers\LanguageController;
use Modules\Configuration\Presentation\Http\Controllers\TimezoneController;

Route::prefix('api/configuration')
    ->middleware('api')
    ->name('configuration.')
    ->group(function (): void {
        $keyPattern = '[A-Za-z0-9._-]+';

        Route::get('entries', [ConfigurationController::class, 'index'])->name('entries.index');
        Route::post('entries', [ConfigurationController::class, 'store'])->name('entries.store');
        Route::get('entries/{key}', [ConfigurationController::class, 'show'])
            ->where('key', $keyPattern)
            ->name('entries.show');
        Route::put('entries/{key}', [ConfigurationController::class, 'update'])
            ->where('key', $keyPattern)
            ->name('entries.update');
        Route::delete('entries/{key}', [ConfigurationController::class, 'destroy'])
            ->where('key', $keyPattern)
            ->name('entries.destroy');
        Route::post('cache/clear', [ConfigurationController::class, 'clearCache'])->name('cache.clear');

        Route::get('countries', [CountryController::class, 'index'])->name('countries.index');
        Route::post('countries', [CountryController::class, 'store'])->name('countries.store');
        Route::get('countries/{country}', [CountryController::class, 'show'])->name('countries.show');
        Route::put('countries/{country}', [CountryController::class, 'update'])->name('countries.update');
        Route::delete('countries/{country}', [CountryController::class, 'destroy'])->name('countries.destroy');

        Route::get('currencies', [CurrencyController::class, 'index'])->name('currencies.index');
        Route::post('currencies', [CurrencyController::class, 'store'])->name('currencies.store');
        Route::get('currencies/{currency}', [CurrencyController::class, 'show'])->name('currencies.show');
        Route::put('currencies/{currency}', [CurrencyController::class, 'update'])->name('currencies.update');
        Route::delete('currencies/{currency}', [CurrencyController::class, 'destroy'])->name('currencies.destroy');

        Route::get('languages', [LanguageController::class, 'index'])->name('languages.index');
        Route::post('languages', [LanguageController::class, 'store'])->name('languages.store');
        Route::get('languages/{language}', [LanguageController::class, 'show'])->name('languages.show');
        Route::put('languages/{language}', [LanguageController::class, 'update'])->name('languages.update');
        Route::delete('languages/{language}', [LanguageController::class, 'destroy'])->name('languages.destroy');

        Route::get('timezones', [TimezoneController::class, 'index'])->name('timezones.index');
        Route::post('timezones', [TimezoneController::class, 'store'])->name('timezones.store');
        Route::get('timezones/{timezone}', [TimezoneController::class, 'show'])->name('timezones.show');
        Route::put('timezones/{timezone}', [TimezoneController::class, 'update'])->name('timezones.update');
        Route::delete('timezones/{timezone}', [TimezoneController::class, 'destroy'])->name('timezones.destroy');
    });
