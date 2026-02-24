<?php

use Illuminate\Support\Facades\Route;
use Modules\FieldService\Presentation\Controllers\ServiceOrderController;
use Modules\FieldService\Presentation\Controllers\ServiceTeamController;

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::apiResource('field-service/teams', ServiceTeamController::class);
    Route::get('field-service/orders', [ServiceOrderController::class, 'index']);
    Route::post('field-service/orders', [ServiceOrderController::class, 'store']);
    Route::get('field-service/orders/{id}', [ServiceOrderController::class, 'show']);
    Route::delete('field-service/orders/{id}', [ServiceOrderController::class, 'destroy']);
    Route::post('field-service/orders/{id}/assign', [ServiceOrderController::class, 'assign']);
    Route::post('field-service/orders/{id}/complete', [ServiceOrderController::class, 'complete']);
});
