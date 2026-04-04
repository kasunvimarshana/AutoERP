<?php

use Illuminate\Support\Facades\Route;
use Modules\Dispatch\Infrastructure\Http\Controllers\DispatchController;

Route::prefix('dispatches')->group(function () {
    Route::get('/',                   [DispatchController::class, 'index']);
    Route::post('/',                  [DispatchController::class, 'store']);
    Route::get('/{id}',               [DispatchController::class, 'show']);
    Route::patch('/{id}',             [DispatchController::class, 'update']);
    Route::delete('/{id}',            [DispatchController::class, 'destroy']);
    Route::post('/{id}/process',      [DispatchController::class, 'process']);
    Route::post('/{id}/dispatch',     [DispatchController::class, 'dispatch']);
    Route::post('/{id}/deliver',      [DispatchController::class, 'deliver']);
});
