<?php

use Illuminate\Support\Facades\Route;
use Modules\Tax\Presentation\Controllers\TaxRateController;

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::get('tax/rates', [TaxRateController::class, 'index']);
    Route::get('tax/rates/active', [TaxRateController::class, 'active']);
    Route::post('tax/rates', [TaxRateController::class, 'store']);
    Route::get('tax/rates/{id}', [TaxRateController::class, 'show']);
    Route::put('tax/rates/{id}', [TaxRateController::class, 'update']);
    Route::delete('tax/rates/{id}', [TaxRateController::class, 'destroy']);
    Route::post('tax/rates/{id}/deactivate', [TaxRateController::class, 'deactivate']);
});
