<?php

use Illuminate\Support\Facades\Route;
use Modules\Accounting\Http\Controllers\Api\AccountController;
use Modules\Accounting\Http\Controllers\Api\FiscalPeriodController;
use Modules\Accounting\Http\Controllers\Api\InvoiceController;
use Modules\Accounting\Http\Controllers\Api\JournalEntryController;
use Modules\Accounting\Http\Controllers\Api\JournalEntryLineController;
use Modules\Accounting\Http\Controllers\Api\PaymentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your module.
|
*/

Route::group(['prefix' => 'v1', 'middleware' => ['api']], function () {
    // Accounts
    Route::apiResource('accounts', AccountController::class);
    Route::get('accounts/chart-of-accounts', [AccountController::class, 'chartOfAccounts']);
    Route::get('accounts/by-type/{type}', [AccountController::class, 'byType']);
    Route::get('accounts/search', [AccountController::class, 'search']);

    // Journal Entries
    Route::apiResource('journal-entries', JournalEntryController::class);
    Route::post('journal-entries/{id}/post', [JournalEntryController::class, 'post']);
    Route::post('journal-entries/{id}/reverse', [JournalEntryController::class, 'reverse']);
    Route::get('journal-entries/{id}/check-balance', [JournalEntryController::class, 'checkBalance']);

    // Journal Entry Lines
    Route::apiResource('journal-entry-lines', JournalEntryLineController::class);
    Route::get('journal-entries/{entryId}/lines', [JournalEntryLineController::class, 'byEntry']);

    // Invoices
    Route::apiResource('invoices', InvoiceController::class);
    Route::post('invoices/{id}/send', [InvoiceController::class, 'send']);
    Route::post('invoices/{id}/mark-paid', [InvoiceController::class, 'markPaid']);
    Route::get('invoices/overdue', [InvoiceController::class, 'overdue']);
    Route::get('invoices/aging-report', [InvoiceController::class, 'aging']);

    // Payments
    Route::apiResource('payments', PaymentController::class);
    Route::post('payments/{id}/apply-to-invoice', [PaymentController::class, 'applyToInvoice']);
    Route::post('payments/{id}/process', [PaymentController::class, 'process']);

    // Fiscal Periods
    Route::apiResource('fiscal-periods', FiscalPeriodController::class);
    Route::post('fiscal-periods/{id}/open', [FiscalPeriodController::class, 'open']);
    Route::post('fiscal-periods/{id}/close', [FiscalPeriodController::class, 'close']);
    Route::get('fiscal-periods/current', [FiscalPeriodController::class, 'current']);
});
