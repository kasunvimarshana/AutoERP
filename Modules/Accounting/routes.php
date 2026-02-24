<?php

use Illuminate\Support\Facades\Route;
use Modules\Accounting\Presentation\Controllers\AccountController;
use Modules\Accounting\Presentation\Controllers\AccountingPeriodController;
use Modules\Accounting\Presentation\Controllers\JournalEntryController;
use Modules\Accounting\Presentation\Controllers\InvoiceController;
use Modules\Accounting\Presentation\Controllers\BankAccountController;
use Modules\Accounting\Presentation\Controllers\BankTransactionController;

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::apiResource('accounting/accounts', AccountController::class);
    Route::apiResource('accounting/journal-entries', JournalEntryController::class);
    Route::post('accounting/journal-entries/{id}/post', [JournalEntryController::class, 'post']);
    Route::apiResource('accounting/invoices', InvoiceController::class);
    Route::post('accounting/invoices/{id}/post', [InvoiceController::class, 'post']);
    Route::post('accounting/invoices/{id}/payments', [InvoiceController::class, 'recordPayment']);
    Route::post('accounting/invoices/{id}/credit-note', [InvoiceController::class, 'issueCreditNote']);

    // Bank Account Management & Reconciliation
    Route::apiResource('accounting/bank-accounts', BankAccountController::class)->except(['update']);
    Route::apiResource('accounting/bank-transactions', BankTransactionController::class)->except(['update']);
    Route::post('accounting/bank-transactions/{id}/reconcile', [BankTransactionController::class, 'reconcile']);

    // Accounting Period Management
    Route::apiResource('accounting/periods', AccountingPeriodController::class)->except(['update', 'destroy']);
    Route::post('accounting/periods/{id}/close', [AccountingPeriodController::class, 'close']);
    Route::post('accounting/periods/{id}/lock', [AccountingPeriodController::class, 'lock']);
});
