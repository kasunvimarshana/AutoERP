<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Invoice\Http\Controllers\DriverCommissionController;
use Modules\Invoice\Http\Controllers\InvoiceController;
use Modules\Invoice\Http\Controllers\PaymentController;

/*
|--------------------------------------------------------------------------
| Invoice Module API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the Invoice module.
| All routes are prefixed with 'api/v1' and require authentication.
|
*/

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Invoice CRUD
    Route::apiResource('invoices', InvoiceController::class);

    // Invoice Actions
    Route::post('invoices/generate', [InvoiceController::class, 'generateFromJobCard'])
        ->name('invoices.generate');
    Route::get('invoices/search', [InvoiceController::class, 'search'])
        ->name('invoices.search');
    Route::get('invoices/overdue/list', [InvoiceController::class, 'overdue'])
        ->name('invoices.overdue');
    Route::get('invoices/outstanding/list', [InvoiceController::class, 'outstanding'])
        ->name('invoices.outstanding');

    // Payments
    Route::apiResource('payments', PaymentController::class)->only(['index', 'store', 'show']);
    Route::post('payments/{id}/void', [PaymentController::class, 'void'])
        ->name('payments.void');
    Route::get('payments/invoice/{invoiceId}/history', [PaymentController::class, 'historyForInvoice'])
        ->name('payments.invoice-history');

    // Driver Commissions
    Route::apiResource('commissions', DriverCommissionController::class)->only(['index', 'store', 'show']);
    Route::post('commissions/{id}/mark-paid', [DriverCommissionController::class, 'markAsPaid'])
        ->name('commissions.mark-paid');
    Route::get('commissions/driver/{driverId}', [DriverCommissionController::class, 'byDriver'])
        ->name('commissions.by-driver');
    Route::get('commissions/pending/list', [DriverCommissionController::class, 'pending'])
        ->name('commissions.pending');
});
