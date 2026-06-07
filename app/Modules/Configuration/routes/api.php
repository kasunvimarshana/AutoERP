<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Configuration\Http\Controllers\ConfigurationController;
use Modules\Configuration\Http\Controllers\CountryController;
use Modules\Configuration\Http\Controllers\CurrencyController;
use Modules\Configuration\Http\Controllers\LanguageController;
use Modules\Configuration\Http\Controllers\TimezoneController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);

Route::prefix('api/v1')
    ->middleware([
        'api',
        'auth:'.$protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
    ])
    ->name('api.v1.')
    ->group(function (): void {
        $keyPattern = '[A-Za-z0-9._-]+';

        Route::get('configuration/entries', [ConfigurationController::class, 'index'])->name('configuration.entries.index');
        Route::post('configuration/entries', [ConfigurationController::class, 'store'])->name('configuration.entries.store');
        Route::get('configuration/entries/{key}', [ConfigurationController::class, 'show'])
            ->where('key', $keyPattern)
            ->name('configuration.entries.show');
        Route::get('configuration/entries/{key}/resolve', [ConfigurationController::class, 'resolve'])
            ->where('key', $keyPattern)
            ->name('configuration.entries.resolve');
        Route::get('configuration/features/{key}/enabled', [ConfigurationController::class, 'featureEnabled'])
            ->where('key', $keyPattern)
            ->name('configuration.features.enabled');
        Route::put('configuration/entries/{key}', [ConfigurationController::class, 'update'])
            ->where('key', $keyPattern)
            ->name('configuration.entries.update');
        Route::delete('configuration/entries/{key}', [ConfigurationController::class, 'destroy'])
            ->where('key', $keyPattern)
            ->name('configuration.entries.destroy');
        Route::post('configuration/cache/clear', [ConfigurationController::class, 'clearCache'])->name('configuration.cache.clear');

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
