<?php
declare(strict_types=1);
use Illuminate\Support\Facades\Route;
Route::prefix('api')->group(function () {
    Route::prefix('contracts')->group(function () {
        Route::get('/',                 [\Modules\Contract\Infrastructure\Http\Controllers\ContractController::class, 'index']);
        Route::post('/',                [\Modules\Contract\Infrastructure\Http\Controllers\ContractController::class, 'store']);
        Route::get('/{id}',             [\Modules\Contract\Infrastructure\Http\Controllers\ContractController::class, 'show']);
        Route::put('/{id}',             [\Modules\Contract\Infrastructure\Http\Controllers\ContractController::class, 'update']);
        Route::delete('/{id}',          [\Modules\Contract\Infrastructure\Http\Controllers\ContractController::class, 'destroy']);
        Route::post('/{id}/activate',   [\Modules\Contract\Infrastructure\Http\Controllers\ContractController::class, 'activate']);
        Route::post('/{id}/terminate',  [\Modules\Contract\Infrastructure\Http\Controllers\ContractController::class, 'terminate']);
        Route::get('/{id}/lines',       [\Modules\Contract\Infrastructure\Http\Controllers\ContractController::class, 'lines']);
        Route::post('/{id}/lines',      [\Modules\Contract\Infrastructure\Http\Controllers\ContractController::class, 'addLine']);
        Route::get('/expiring',         [\Modules\Contract\Infrastructure\Http\Controllers\ContractController::class, 'expiring']);
    });
});
