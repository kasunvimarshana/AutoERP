<?php
declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Modules\Accounting\Infrastructure\Http\Controllers\AccountController;
use Modules\Accounting\Infrastructure\Http\Controllers\BankAccountController;
use Modules\Accounting\Infrastructure\Http\Controllers\BankTransactionController;
use Modules\Accounting\Infrastructure\Http\Controllers\BudgetController;
use Modules\Accounting\Infrastructure\Http\Controllers\FinancialReportController;
use Modules\Accounting\Infrastructure\Http\Controllers\JournalEntryController;
Route::prefix('api')->middleware(['auth:api'])->group(function () {
    Route::apiResource('accounts', AccountController::class);
    Route::apiResource('journal-entries', JournalEntryController::class)->except(['update']);
    Route::post('journal-entries/{id}/post', [JournalEntryController::class, 'post'])->name('journal-entries.post');
    Route::post('journal-entries/{id}/void', [JournalEntryController::class, 'void'])->name('journal-entries.void');
    Route::apiResource('bank-accounts', BankAccountController::class);
    Route::apiResource('bank-transactions', BankTransactionController::class)->except(['update']);
    Route::post('bank-transactions/{id}/categorize', [BankTransactionController::class, 'categorize'])->name('bank-transactions.categorize');
    Route::post('bank-transactions/import', [BankTransactionController::class, 'importBatch'])->name('bank-transactions.import');
    Route::apiResource('budgets', BudgetController::class);
    Route::get('reports/balance-sheet', [FinancialReportController::class, 'balanceSheet'])->name('reports.balance-sheet');
    Route::get('reports/profit-and-loss', [FinancialReportController::class, 'profitAndLoss'])->name('reports.profit-and-loss');
});
