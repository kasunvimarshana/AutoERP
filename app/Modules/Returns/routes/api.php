<?php
use Illuminate\Support\Facades\Route;
use Modules\Returns\Infrastructure\Http\Controllers\ReturnRequestController;

Route::prefix('api')->group(function () {
    Route::get('returns',              [ReturnRequestController::class, 'index']);
    Route::post('returns',             [ReturnRequestController::class, 'store']);
    Route::get('returns/{id}',         [ReturnRequestController::class, 'show']);
    Route::post('returns/{id}/approve', [ReturnRequestController::class, 'approve']);
    Route::post('returns/{id}/reject',  [ReturnRequestController::class, 'reject']);
    Route::post('returns/{id}/restock', [ReturnRequestController::class, 'restock']);
    Route::post('returns/{id}/complete',[ReturnRequestController::class, 'complete']);
    Route::delete('returns/{id}',      [ReturnRequestController::class, 'destroy']);
});
