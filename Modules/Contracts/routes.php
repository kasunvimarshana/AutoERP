<?php

use Illuminate\Support\Facades\Route;
use Modules\Contracts\Presentation\Controllers\ContractController;

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::get('contracts/contracts', [ContractController::class, 'index']);
    Route::post('contracts/contracts', [ContractController::class, 'store']);
    Route::get('contracts/contracts/{id}', [ContractController::class, 'show']);
    Route::put('contracts/contracts/{id}', [ContractController::class, 'update']);
    Route::delete('contracts/contracts/{id}', [ContractController::class, 'destroy']);
    Route::post('contracts/contracts/{id}/activate', [ContractController::class, 'activate']);
    Route::post('contracts/contracts/{id}/terminate', [ContractController::class, 'terminate']);
});
