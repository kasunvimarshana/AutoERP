<?php

use Illuminate\Support\Facades\Route;
use Modules\Dispatch\Infrastructure\Http\Controllers\DispatchController;
use Modules\Dispatch\Infrastructure\Http\Controllers\DispatchLineController;

Route::apiResource('dispatches', DispatchController::class);
Route::post('dispatches/{id}/confirm', [DispatchController::class, 'confirm']);
Route::post('dispatches/{id}/ship', [DispatchController::class, 'ship']);
Route::post('dispatches/{id}/deliver', [DispatchController::class, 'deliver']);
Route::post('dispatches/{id}/cancel', [DispatchController::class, 'cancel']);
Route::apiResource('dispatch-lines', DispatchLineController::class);
