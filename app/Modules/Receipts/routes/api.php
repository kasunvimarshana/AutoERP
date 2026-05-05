<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Receipts\Infrastructure\Http\Controllers\ReceiptController;

Route::middleware(['auth.configured', 'resolve.tenant'])->group(function (): void {
    Route::get('receipts', [ReceiptController::class, 'index']);
    Route::post('receipts', [ReceiptController::class, 'store']);
    Route::get('receipts/{id}', [ReceiptController::class, 'show']);
    Route::patch('receipts/{id}/status', [ReceiptController::class, 'changeStatus']);
    Route::delete('receipts/{id}', [ReceiptController::class, 'destroy']);
    Route::get('payments/{paymentId}/receipts', [ReceiptController::class, 'byPayment']);
});
