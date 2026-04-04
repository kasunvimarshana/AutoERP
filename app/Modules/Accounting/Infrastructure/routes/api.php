<?php
use Illuminate\Support\Facades\Route;
use Modules\Accounting\Infrastructure\Http\Controllers\AccountController;
use Modules\Accounting\Infrastructure\Http\Controllers\JournalEntryController;
use Modules\Accounting\Infrastructure\Http\Controllers\PaymentController;

Route::prefix('accounts')->group(function () {
    Route::get('/', [AccountController::class, 'index']);
    Route::post('/', [AccountController::class, 'store']);
    Route::get('/{id}', [AccountController::class, 'show']);
    Route::patch('/{id}', [AccountController::class, 'update']);
    Route::delete('/{id}', [AccountController::class, 'destroy']);
});

Route::prefix('journal-entries')->group(function () {
    Route::get('/', [JournalEntryController::class, 'index']);
    Route::post('/', [JournalEntryController::class, 'store']);
    Route::get('/{id}', [JournalEntryController::class, 'show']);
    Route::post('/{id}/post', [JournalEntryController::class, 'post']);
    Route::post('/{id}/reverse', [JournalEntryController::class, 'reverse']);
});

Route::prefix('payments')->group(function () {
    Route::get('/', [PaymentController::class, 'index']);
    Route::post('/', [PaymentController::class, 'store']);
    Route::get('/{id}', [PaymentController::class, 'show']);
    Route::post('/{id}/refund', [PaymentController::class, 'refund']);
});
