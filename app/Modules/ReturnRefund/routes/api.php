<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\ReturnRefund\Infrastructure\Http\Controllers\ReturnRefundController;

Route::middleware(['auth.configured', 'resolve.tenant'])->group(function (): void {
    // Return & Refund resources
    Route::get('return-refunds', [ReturnRefundController::class, 'index']);
    Route::post('return-refunds', [ReturnRefundController::class, 'store']);
    Route::get('return-refunds/{id}', [ReturnRefundController::class, 'show']);
    Route::put('return-refunds/{id}', [ReturnRefundController::class, 'update']);
    Route::delete('return-refunds/{id}', [ReturnRefundController::class, 'destroy']);
    Route::patch('return-refunds/{id}/status', [ReturnRefundController::class, 'changeStatus']);

    // Returns by rental
    Route::get('rentals/{rentalId}/returns', [ReturnRefundController::class, 'byRental']);
});
