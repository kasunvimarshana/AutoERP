<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\UOM\Presentation\Http\Controllers\UnitOfMeasureController;
use Modules\UOM\Presentation\Http\Controllers\UomConversionController;

Route::prefix('api/uom')
    ->middleware('api')
    ->name('uom.')
    ->group(function (): void {
        Route::prefix('tenants/{tenant}')
            ->name('tenants.')
            ->group(function (): void {
                Route::post('conversions/convert', [UomConversionController::class, 'convert'])
                    ->name('conversions.convert');

                Route::apiResource('units', UnitOfMeasureController::class)
                    ->parameters(['units' => 'unit']);

                Route::apiResource('conversions', UomConversionController::class);
            });
    });
