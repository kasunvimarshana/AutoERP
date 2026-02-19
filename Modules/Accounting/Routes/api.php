<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Accounting\Http\Controllers\AccountController;
use Modules\Accounting\Http\Controllers\FiscalPeriodController;
use Modules\Accounting\Http\Controllers\JournalEntryController;
use Modules\Accounting\Http\Controllers\ReportController;

Route::middleware(['auth:sanctum', 'tenant.context'])->prefix('api/accounting')->group(function () {

    // Accounts
    Route::apiResource('accounts', AccountController::class);
    Route::get('accounts/{account}/balance', [AccountController::class, 'balance']);

    // Journal Entries
    Route::apiResource('journal-entries', JournalEntryController::class);
    Route::post('journal-entries/{journal_entry}/post', [JournalEntryController::class, 'post']);
    Route::post('journal-entries/{journal_entry}/reverse', [JournalEntryController::class, 'reverse']);

    // Fiscal Periods
    Route::apiResource('fiscal-periods', FiscalPeriodController::class);
    Route::post('fiscal-periods/{fiscal_period}/close', [FiscalPeriodController::class, 'close']);
    Route::post('fiscal-periods/{fiscal_period}/reopen', [FiscalPeriodController::class, 'reopen']);
    Route::post('fiscal-periods/{fiscal_period}/lock', [FiscalPeriodController::class, 'lock']);

    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('chart-of-accounts', [ReportController::class, 'chartOfAccounts']);
        Route::get('trial-balance', [ReportController::class, 'trialBalance']);
        Route::get('balance-sheet', [ReportController::class, 'balanceSheet']);
        Route::get('income-statement', [ReportController::class, 'incomeStatement']);
        Route::get('cash-flow-statement', [ReportController::class, 'cashFlowStatement']);
        Route::get('account-ledger', [ReportController::class, 'accountLedger']);
    });
});
