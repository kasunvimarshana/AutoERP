<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Finance\Infrastructure\Http\Controllers\AccountController;
use Modules\Finance\Infrastructure\Http\Controllers\ApprovalRequestController;
use Modules\Finance\Infrastructure\Http\Controllers\ApprovalWorkflowConfigController;
use Modules\Finance\Infrastructure\Http\Controllers\ApTransactionController;
use Modules\Finance\Infrastructure\Http\Controllers\ArTransactionController;
use Modules\Finance\Infrastructure\Http\Controllers\BankAccountController;
use Modules\Finance\Infrastructure\Http\Controllers\BankCategoryRuleController;
use Modules\Finance\Infrastructure\Http\Controllers\BankReconciliationController;
use Modules\Finance\Infrastructure\Http\Controllers\BankTransactionController;
use Modules\Finance\Infrastructure\Http\Controllers\CostCenterController;
use Modules\Finance\Infrastructure\Http\Controllers\CreditMemoController;
use Modules\Finance\Infrastructure\Http\Controllers\FinancialReportController;
use Modules\Finance\Infrastructure\Http\Controllers\FiscalPeriodController;
use Modules\Finance\Infrastructure\Http\Controllers\FiscalYearController;
use Modules\Finance\Infrastructure\Http\Controllers\JournalEntryController;
use Modules\Finance\Infrastructure\Http\Controllers\NumberingSequenceController;
use Modules\Finance\Infrastructure\Http\Controllers\PaymentAllocationController;
use Modules\Finance\Infrastructure\Http\Controllers\PaymentController;
use Modules\Finance\Infrastructure\Http\Controllers\PaymentMethodController;
use Modules\Finance\Infrastructure\Http\Controllers\PaymentTermController;

Route::middleware(['auth.configured', 'resolve.tenant'])->group(function (): void {
    // Chart of Accounts & General Ledger
    Route::apiResource('accounts', AccountController::class);

    // Fiscal Calendar
    Route::apiResource('fiscal-years', FiscalYearController::class);
    Route::apiResource('fiscal-periods', FiscalPeriodController::class);

    // Journal Entries
    Route::apiResource('journal-entries', JournalEntryController::class);
    Route::post('journal-entries/{journal_entry}/post', [JournalEntryController::class, 'post']);

    // Payment lifecycle
    Route::post('payments/{payment}/post', [PaymentController::class, 'post']);
    Route::post('payments/{payment}/void', [PaymentController::class, 'void']);

    // Credit Memo lifecycle
    Route::post('credit-memos/{credit_memo}/issue', [CreditMemoController::class, 'issue']);
    Route::post('credit-memos/{credit_memo}/apply', [CreditMemoController::class, 'apply']);
    Route::post('credit-memos/{credit_memo}/void', [CreditMemoController::class, 'voidMemo']);

    // Bank Reconciliation lifecycle
    Route::post('bank-reconciliations/{bank_reconciliation}/complete', [BankReconciliationController::class, 'complete']);

    // Approval Request lifecycle
    Route::post('approval-requests/{approval_request}/approve', [ApprovalRequestController::class, 'approve']);
    Route::post('approval-requests/{approval_request}/reject', [ApprovalRequestController::class, 'reject']);
    Route::post('approval-requests/{approval_request}/cancel', [ApprovalRequestController::class, 'cancel']);

    // Bank Transaction categorization
    Route::post('bank-transactions/{bank_transaction}/categorize', [BankTransactionController::class, 'categorize']);

    // AR/AP reconciliation
    Route::post('ar-transactions/{ar_transaction}/reconcile', [ArTransactionController::class, 'reconcile']);
    Route::post('ap-transactions/{ap_transaction}/reconcile', [ApTransactionController::class, 'reconcile']);

    // Generate next document number
    Route::post('numbering-sequences/{numbering_sequence}/next', [NumberingSequenceController::class, 'next']);

    // Cost Centers
    Route::apiResource('cost-centers', CostCenterController::class);

    // AP/AR Configuration
    Route::apiResource('payment-terms', PaymentTermController::class);

    // Document Numbering
    Route::apiResource('numbering-sequences', NumberingSequenceController::class);

    // Payments
    Route::apiResource('payment-methods', PaymentMethodController::class);
    Route::apiResource('payments', PaymentController::class);
    Route::apiResource('payment-allocations', PaymentAllocationController::class)->only(['index', 'store', 'show', 'destroy']);

    // Accounts Receivable
    Route::apiResource('ar-transactions', ArTransactionController::class);

    // Accounts Payable
    Route::apiResource('ap-transactions', ApTransactionController::class);

    // Credit Memos
    Route::apiResource('credit-memos', CreditMemoController::class);

    // Banking
    Route::apiResource('bank-accounts', BankAccountController::class);
    Route::apiResource('bank-category-rules', BankCategoryRuleController::class);
    Route::apiResource('bank-transactions', BankTransactionController::class);
    Route::apiResource('bank-reconciliations', BankReconciliationController::class);

    // Approval Workflows
    Route::apiResource('approval-workflow-configs', ApprovalWorkflowConfigController::class);
    Route::apiResource('approval-requests', ApprovalRequestController::class);

    // Financial Reports
    Route::get('reports/general-ledger', [FinancialReportController::class, 'generalLedger']);
    Route::get('reports/trial-balance', [FinancialReportController::class, 'trialBalance']);
    Route::get('reports/balance-sheet', [FinancialReportController::class, 'balanceSheet']);
    Route::get('reports/profit-loss', [FinancialReportController::class, 'profitLoss']);
});
