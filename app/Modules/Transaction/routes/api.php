<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Transaction\Infrastructure\Http\Controllers\JournalEntryController;
use Modules\Transaction\Infrastructure\Http\Controllers\TransactionController;

Route::prefix('transactions')->group(function () {
    Route::get('/', [TransactionController::class, 'index']);
    Route::post('/', [TransactionController::class, 'store']);
    Route::get('/{id}', [TransactionController::class, 'show']);
    Route::put('/{id}', [TransactionController::class, 'update']);
    Route::delete('/{id}', [TransactionController::class, 'destroy']);
    Route::post('/{id}/post', [TransactionController::class, 'post']);
    Route::post('/{id}/void', [TransactionController::class, 'void']);
});

Route::prefix('journal-entries')->group(function () {
    Route::get('/', [JournalEntryController::class, 'index']);
    Route::post('/', [JournalEntryController::class, 'store']);
    Route::get('/{id}', [JournalEntryController::class, 'show']);
    Route::put('/{id}', [JournalEntryController::class, 'update']);
    Route::post('/{id}/post', [JournalEntryController::class, 'post']);
});
