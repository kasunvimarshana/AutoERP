<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Tax\Infrastructure\Http\Controllers\TaxGroupController;
use Modules\Tax\Infrastructure\Http\Controllers\TaxGroupRateController;

Route::prefix('api')->middleware(['auth:api'])->group(function (): void {
    Route::apiResource('tax-groups', TaxGroupController::class);
    Route::post('tax-groups/{taxGroup}/calculate', [TaxGroupController::class, 'calculate']);
    Route::apiResource('tax-groups.tax-group-rates', TaxGroupRateController::class);
});
