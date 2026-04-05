<?php
use Illuminate\Support\Facades\Route;
use Modules\Dispatch\Infrastructure\Http\Controllers\DispatchController;
Route::prefix('api')->group(function () {
    Route::get('dispatches', [DispatchController::class, 'index']);
    Route::post('dispatches', [DispatchController::class, 'store']);
    Route::get('dispatches/{id}', [DispatchController::class, 'show']);
    Route::post('dispatches/{id}/ship', [DispatchController::class, 'ship']);
    Route::delete('dispatches/{id}', [DispatchController::class, 'destroy']);
});
