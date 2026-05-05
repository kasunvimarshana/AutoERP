<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Payments\Infrastructure\Http\Controllers\PaymentController;

Route::middleware(['auth.configured', 'resolve.tenant'])->group(function (): void {
    Route::get('payments', [PaymentController::class, 'index']);
    Route::post('payments', [PaymentController::class, 'store']);
    Route::get('payments/{id}', [PaymentController::class, 'show']);
    Route::patch('payments/{id}/status', [PaymentController::class, 'changeStatus']);
    Route::delete('payments/{id}', [PaymentController::class, 'destroy']);
    Route::get('invoices/{invoiceId}/payments', [PaymentController::class, 'byInvoice']);
});
