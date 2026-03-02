<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Accounting\Interfaces\Http\Controllers\AccountingController;

/*
|--------------------------------------------------------------------------
| Accounting Module API Routes
|--------------------------------------------------------------------------
|
| All routes are versioned under /api/v1
|
*/

Route::middleware('auth:api')->prefix('api/v1')->name('accounting.')->group(function (): void {
    Route::post('journals', [AccountingController::class, 'createEntry'])->name('journals.store');
    Route::get('journals', [AccountingController::class, 'listEntries'])->name('journals.index');
    Route::get('journals/{id}', [AccountingController::class, 'showJournalEntry'])->name('journals.show');
    Route::post('journals/{id}/post', [AccountingController::class, 'postEntry'])->name('journals.post');
    Route::get('accounting/accounts', [AccountingController::class, 'listAccounts'])->name('accounts.index');
    Route::post('accounting/accounts', [AccountingController::class, 'createAccount'])->name('accounts.store');
    Route::get('accounting/accounts/{id}', [AccountingController::class, 'showAccount'])->name('accounts.show');
    Route::put('accounting/accounts/{id}', [AccountingController::class, 'updateAccount'])->name('accounts.update');
    Route::get('accounting/fiscal-periods', [AccountingController::class, 'listFiscalPeriods'])->name('fiscal-periods.index');
    Route::post('accounting/fiscal-periods', [AccountingController::class, 'createFiscalPeriod'])->name('fiscal-periods.store');
    Route::get('accounting/fiscal-periods/{id}', [AccountingController::class, 'showFiscalPeriod'])->name('fiscal-periods.show');
    Route::post('accounting/fiscal-periods/{id}/close', [AccountingController::class, 'closeFiscalPeriod'])->name('fiscal-periods.close');
    Route::get('accounting/fiscal-periods/{id}/trial-balance', [AccountingController::class, 'getTrialBalance'])->name('fiscal-periods.trial-balance');
    Route::get('accounting/fiscal-periods/{id}/profit-and-loss', [AccountingController::class, 'getProfitAndLoss'])->name('fiscal-periods.pnl');
    Route::get('accounting/fiscal-periods/{id}/balance-sheet', [AccountingController::class, 'getBalanceSheet'])->name('fiscal-periods.balance-sheet');
});
