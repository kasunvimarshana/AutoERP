<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Returns\Infrastructure\Http\Controllers\PurchaseReturnController;
use Modules\Returns\Infrastructure\Http\Controllers\ReturnLineController;
use Modules\Returns\Infrastructure\Http\Controllers\SalesReturnController;

Route::prefix('api')->middleware(['auth:api'])->group(function () {
    Route::get('purchase-returns', [PurchaseReturnController::class, 'index']);
    Route::post('purchase-returns', [PurchaseReturnController::class, 'store']);
    Route::get('purchase-returns/{id}', [PurchaseReturnController::class, 'show']);
    Route::put('purchase-returns/{id}', [PurchaseReturnController::class, 'update']);
    Route::delete('purchase-returns/{id}', [PurchaseReturnController::class, 'destroy']);
    Route::post('purchase-returns/{id}/approve', [PurchaseReturnController::class, 'approve']);
    Route::post('purchase-returns/{id}/complete', [PurchaseReturnController::class, 'complete']);
    Route::post('purchase-returns/{id}/cancel', [PurchaseReturnController::class, 'cancel']);

    Route::get('sales-returns', [SalesReturnController::class, 'index']);
    Route::post('sales-returns', [SalesReturnController::class, 'store']);
    Route::get('sales-returns/{id}', [SalesReturnController::class, 'show']);
    Route::put('sales-returns/{id}', [SalesReturnController::class, 'update']);
    Route::delete('sales-returns/{id}', [SalesReturnController::class, 'destroy']);
    Route::post('sales-returns/{id}/approve', [SalesReturnController::class, 'approve']);
    Route::post('sales-returns/{id}/complete', [SalesReturnController::class, 'complete']);
    Route::post('sales-returns/{id}/cancel', [SalesReturnController::class, 'cancel']);

    Route::get('return-lines', [ReturnLineController::class, 'index']);
    Route::post('return-lines', [ReturnLineController::class, 'store']);
    Route::get('return-lines/{id}', [ReturnLineController::class, 'show']);
    Route::put('return-lines/{id}', [ReturnLineController::class, 'update']);
    Route::delete('return-lines/{id}', [ReturnLineController::class, 'destroy']);
});
