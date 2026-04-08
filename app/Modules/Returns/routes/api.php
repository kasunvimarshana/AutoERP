<?php

use Illuminate\Support\Facades\Route;
use Modules\Returns\Infrastructure\Http\Controllers\CreditMemoController;
use Modules\Returns\Infrastructure\Http\Controllers\ReturnAuthorizationController;
use Modules\Returns\Infrastructure\Http\Controllers\StockReturnController;
use Modules\Returns\Infrastructure\Http\Controllers\StockReturnLineController;

Route::prefix('returns')->group(function () {
    Route::get('/', [StockReturnController::class, 'index']);
    Route::post('/', [StockReturnController::class, 'store']);
    Route::get('/{id}', [StockReturnController::class, 'show']);
    Route::put('/{id}', [StockReturnController::class, 'update']);
    Route::delete('/{id}', [StockReturnController::class, 'destroy']);
    Route::post('/{id}/approve', [StockReturnController::class, 'approve']);
    Route::post('/{id}/reject', [StockReturnController::class, 'reject']);
    Route::post('/{id}/complete', [StockReturnController::class, 'complete']);
    Route::post('/{id}/cancel', [StockReturnController::class, 'cancel']);
    Route::post('/{id}/issue-credit-memo', [StockReturnController::class, 'issueCreditMemo']);
});

Route::prefix('return-lines')->group(function () {
    Route::get('/', [StockReturnLineController::class, 'index']);
    Route::post('/', [StockReturnLineController::class, 'store']);
    Route::get('/{id}', [StockReturnLineController::class, 'show']);
    Route::put('/{id}', [StockReturnLineController::class, 'update']);
    Route::delete('/{id}', [StockReturnLineController::class, 'destroy']);
    Route::post('/{id}/pass-quality-check', [StockReturnLineController::class, 'passQualityCheck']);
    Route::post('/{id}/fail-quality-check', [StockReturnLineController::class, 'failQualityCheck']);
});

Route::prefix('credit-memos')->group(function () {
    Route::get('/', [CreditMemoController::class, 'index']);
    Route::post('/', [CreditMemoController::class, 'store']);
    Route::get('/{id}', [CreditMemoController::class, 'show']);
    Route::delete('/{id}', [CreditMemoController::class, 'destroy']);
    Route::post('/{id}/issue', [CreditMemoController::class, 'issue']);
    Route::post('/{id}/apply', [CreditMemoController::class, 'apply']);
    Route::post('/{id}/void', [CreditMemoController::class, 'void']);
});

Route::prefix('return-authorizations')->group(function () {
    Route::get('/', [ReturnAuthorizationController::class, 'index']);
    Route::post('/', [ReturnAuthorizationController::class, 'store']);
    Route::get('/{id}', [ReturnAuthorizationController::class, 'show']);
    Route::delete('/{id}', [ReturnAuthorizationController::class, 'destroy']);
    Route::post('/{id}/approve', [ReturnAuthorizationController::class, 'approve']);
    Route::post('/{id}/cancel', [ReturnAuthorizationController::class, 'cancel']);
    Route::post('/{id}/expire', [ReturnAuthorizationController::class, 'expire']);
});

