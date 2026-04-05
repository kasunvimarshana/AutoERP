<?php
declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    Route::prefix('tax-groups')->group(function () {
        Route::get('/',                       [\Modules\Tax\Infrastructure\Http\Controllers\TaxGroupController::class, 'index']);
        Route::post('/',                      [\Modules\Tax\Infrastructure\Http\Controllers\TaxGroupController::class, 'store']);
        Route::get('/{id}',                   [\Modules\Tax\Infrastructure\Http\Controllers\TaxGroupController::class, 'show']);
        Route::put('/{id}',                   [\Modules\Tax\Infrastructure\Http\Controllers\TaxGroupController::class, 'update']);
        Route::delete('/{id}',                [\Modules\Tax\Infrastructure\Http\Controllers\TaxGroupController::class, 'destroy']);
        Route::post('/{id}/calculate',        [\Modules\Tax\Infrastructure\Http\Controllers\TaxGroupController::class, 'calculate']);
    });
});
