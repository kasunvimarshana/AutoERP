<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Accounting\Interfaces\Http\Controllers\AccountController;
use Modules\Accounting\Interfaces\Http\Controllers\JournalEntryController;

Route::prefix('api/v1')->group(function (): void {
    Route::get('/accounts', [AccountController::class, 'index']);
    Route::post('/accounts', [AccountController::class, 'store']);
    Route::get('/accounts/{id}', [AccountController::class, 'show']);
    Route::put('/accounts/{id}', [AccountController::class, 'update']);
    Route::delete('/accounts/{id}', [AccountController::class, 'destroy']);

    Route::get('/accounting/journal-entries', [JournalEntryController::class, 'index']);
    Route::post('/accounting/journal-entries', [JournalEntryController::class, 'store']);
    Route::get('/accounting/journal-entries/{id}', [JournalEntryController::class, 'show']);
    Route::post('/accounting/journal-entries/{id}/post', [JournalEntryController::class, 'post']);
    Route::delete('/accounting/journal-entries/{id}', [JournalEntryController::class, 'destroy']);
});
