<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Transaction\Infrastructure\Http\Controllers\TransactionController;
use Modules\Transaction\Infrastructure\Http\Controllers\TransactionLineController;

Route::prefix('api')->middleware(['auth:api'])->group(function (): void {
    Route::get('transactions', [TransactionController::class, 'index']);
    Route::post('transactions', [TransactionController::class, 'store']);
    Route::get('transactions/{id}', [TransactionController::class, 'show']);
    Route::put('transactions/{id}', [TransactionController::class, 'update']);
    Route::delete('transactions/{id}', [TransactionController::class, 'destroy']);
    Route::post('transactions/{id}/post', [TransactionController::class, 'post']);
    Route::post('transactions/{id}/void', [TransactionController::class, 'void']);
    Route::get('transactions-by-date', [TransactionController::class, 'byDateRange']);

    Route::get('transaction-lines', [TransactionLineController::class, 'index']);
    Route::post('transaction-lines', [TransactionLineController::class, 'store']);
    Route::get('transaction-lines/{id}', [TransactionLineController::class, 'show']);
    Route::put('transaction-lines/{id}', [TransactionLineController::class, 'update']);
    Route::delete('transaction-lines/{id}', [TransactionLineController::class, 'destroy']);
});
