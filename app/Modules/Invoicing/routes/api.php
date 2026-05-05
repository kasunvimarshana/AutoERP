<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Invoicing\Infrastructure\Http\Controllers\InvoiceController;

Route::middleware(['auth.configured', 'resolve.tenant'])->group(function (): void {
    Route::get('invoices', [InvoiceController::class, 'index']);
    Route::post('invoices', [InvoiceController::class, 'store']);
    Route::get('invoices/{id}', [InvoiceController::class, 'show']);
    Route::patch('invoices/{id}/status', [InvoiceController::class, 'changeStatus']);
    Route::patch('invoices/{id}/payment', [InvoiceController::class, 'recordPayment']);
    Route::delete('invoices/{id}', [InvoiceController::class, 'destroy']);
    Route::get('entity-invoices/{entityType}/{entityId}', [InvoiceController::class, 'byEntity']);
});
