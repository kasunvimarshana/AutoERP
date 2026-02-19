<?php

use Illuminate\Support\Facades\Route;
use Modules\Sales\Http\Controllers\InvoiceController;
use Modules\Sales\Http\Controllers\OrderController;
use Modules\Sales\Http\Controllers\QuotationController;

/*
|--------------------------------------------------------------------------
| Sales API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/v1')->middleware(['api', 'jwt.auth'])->group(function () {

    // Quotations
    Route::apiResource('quotations', QuotationController::class);
    Route::post('quotations/{quotation}/send', [QuotationController::class, 'send']);
    Route::post('quotations/{quotation}/accept', [QuotationController::class, 'accept']);
    Route::post('quotations/{quotation}/reject', [QuotationController::class, 'reject']);
    Route::post('quotations/{quotation}/convert-to-order', [QuotationController::class, 'convertToOrder']);

    // Orders
    Route::apiResource('orders', OrderController::class);
    Route::post('orders/{order}/confirm', [OrderController::class, 'confirm']);
    Route::post('orders/{order}/cancel', [OrderController::class, 'cancel']);
    Route::post('orders/{order}/complete', [OrderController::class, 'complete']);
    Route::post('orders/{order}/create-invoice', [OrderController::class, 'createInvoice']);

    // Invoices
    Route::apiResource('invoices', InvoiceController::class);
    Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send']);
    Route::post('invoices/{invoice}/record-payment', [InvoiceController::class, 'recordPayment']);
    Route::post('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel']);
});
