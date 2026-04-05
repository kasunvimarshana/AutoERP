<?php
use Illuminate\Support\Facades\Route;
use Modules\Accounting\Infrastructure\Http\Controllers\AccountController;
use Modules\Accounting\Infrastructure\Http\Controllers\BankAccountController;
use Modules\Accounting\Infrastructure\Http\Controllers\BankTransactionController;
use Modules\Accounting\Infrastructure\Http\Controllers\BudgetController;
use Modules\Accounting\Infrastructure\Http\Controllers\ExpenseCategoryController;
use Modules\Accounting\Infrastructure\Http\Controllers\FinancialReportController;
use Modules\Accounting\Infrastructure\Http\Controllers\JournalEntryController;
use Modules\Accounting\Infrastructure\Http\Controllers\TransactionRuleController;

Route::prefix('api')->group(function () {
    // ── Chart of Accounts ─────────────────────────────────────────────────
    Route::apiResource('accounts', AccountController::class);

    // ── Journal Entries ───────────────────────────────────────────────────
    Route::get('journal-entries', [JournalEntryController::class, 'index']);
    Route::post('journal-entries', [JournalEntryController::class, 'store']);
    Route::get('journal-entries/{id}', [JournalEntryController::class, 'show']);
    Route::post('journal-entries/{id}/post', [JournalEntryController::class, 'post']);
    Route::delete('journal-entries/{id}', [JournalEntryController::class, 'destroy']);

    // ── Bank Accounts ─────────────────────────────────────────────────────
    Route::apiResource('bank-accounts', BankAccountController::class);

    // ── Bank Transactions ─────────────────────────────────────────────────
    Route::post('bank-accounts/{bankAccountId}/transactions/import', [BankTransactionController::class, 'import']);
    Route::post('bank-transactions/{id}/categorize', [BankTransactionController::class, 'categorize']);
    Route::post('bank-transactions/auto-apply-rules', [BankTransactionController::class, 'autoApplyRules']);
    Route::post('bank-transactions/bulk-reclassify', [BankTransactionController::class, 'bulkReclassify']);

    // ── Expense Categories ────────────────────────────────────────────────
    Route::apiResource('expense-categories', ExpenseCategoryController::class);

    // ── Transaction Rules ─────────────────────────────────────────────────
    Route::apiResource('transaction-rules', TransactionRuleController::class);

    // ── Financial Reports ─────────────────────────────────────────────────
    Route::get('reports/balance-sheet', [FinancialReportController::class, 'balanceSheet']);
    Route::get('reports/profit-and-loss', [FinancialReportController::class, 'profitAndLoss']);

    // ── Budgets ───────────────────────────────────────────────────────────
    Route::apiResource('budgets', BudgetController::class);
});
