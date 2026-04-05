<?php
declare(strict_types=1);
use Illuminate\Support\Facades\Route;
Route::prefix('api')->group(function () {
    Route::prefix('assets')->group(function () {
        Route::get('/',           [\Modules\Asset\Infrastructure\Http\Controllers\FixedAssetController::class, 'index']);
        Route::post('/',          [\Modules\Asset\Infrastructure\Http\Controllers\FixedAssetController::class, 'store']);
        Route::get('/{id}',       [\Modules\Asset\Infrastructure\Http\Controllers\FixedAssetController::class, 'show']);
        Route::put('/{id}',       [\Modules\Asset\Infrastructure\Http\Controllers\FixedAssetController::class, 'update']);
        Route::delete('/{id}',    [\Modules\Asset\Infrastructure\Http\Controllers\FixedAssetController::class, 'destroy']);
        Route::post('/{id}/dispose', [\Modules\Asset\Infrastructure\Http\Controllers\FixedAssetController::class, 'dispose']);
        Route::post('/{id}/sell',    [\Modules\Asset\Infrastructure\Http\Controllers\FixedAssetController::class, 'sell']);
        Route::post('/{id}/depreciate', [\Modules\Asset\Infrastructure\Http\Controllers\FixedAssetController::class, 'depreciate']);
        Route::get('/{id}/depreciations', [\Modules\Asset\Infrastructure\Http\Controllers\FixedAssetController::class, 'depreciations']);
    });
});
