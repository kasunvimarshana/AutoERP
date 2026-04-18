<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Finance\Infrastructure\Http\Controllers\AccountController;
use Modules\Finance\Infrastructure\Http\Controllers\FiscalPeriodController;
use Modules\Finance\Infrastructure\Http\Controllers\FiscalYearController;
use Modules\Finance\Infrastructure\Http\Controllers\JournalEntryController;

Route::middleware(['auth:api', 'resolve.tenant'])->group(function (): void {
    Route::apiResource('accounts', AccountController::class);
    Route::apiResource('fiscal-years', FiscalYearController::class);
    Route::apiResource('fiscal-periods', FiscalPeriodController::class);
    Route::apiResource('journal-entries', JournalEntryController::class);
    Route::post('journal-entries/{journal_entry}/post', [JournalEntryController::class, 'post']);
});
