<?php

use App\Modules\InvoicingManagement\Http\Controllers\InvoiceController;
use App\Modules\InvoicingManagement\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Invoicing Management API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/v1')->middleware(['api'])->group(function () {
    
    // Invoice routes
    Route::apiResource('invoices', InvoiceController::class);
    Route::post('invoices/generate-from-job-card/{jobCardId}', [InvoiceController::class, 'generateFromJobCard']);
    Route::post('invoices/{id}/send', [InvoiceController::class, 'send']);
    Route::post('invoices/{id}/pay', [InvoiceController::class, 'pay']);

    // Payment routes
    Route::apiResource('payments', PaymentController::class);
    Route::post('payments/{id}/apply', [PaymentController::class, 'applyToInvoice']);
});
