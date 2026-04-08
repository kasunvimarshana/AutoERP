<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Taxation\Infrastructure\Http\Controllers\TaxRateController;
use Modules\Taxation\Infrastructure\Http\Controllers\TaxRuleController;

Route::apiResource('tax-rates', TaxRateController::class);
Route::post('tax-rates/{id}/activate', [TaxRateController::class, 'activate']);
Route::post('tax-rates/{id}/deactivate', [TaxRateController::class, 'deactivate']);
Route::apiResource('tax-rules', TaxRuleController::class);
