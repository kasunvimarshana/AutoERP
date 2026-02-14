<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Billing\Http\Controllers\InvoiceController;
use App\Modules\Billing\Http\Controllers\InvoiceItemController;
use App\Modules\Billing\Http\Controllers\PaymentController;

Route::prefix('api/billing')->middleware(['api'])->group(function () {
    // Invoice routes
    Route::prefix('invoices')->group(function () {
        Route::get('/', [InvoiceController::class, 'index']);
        Route::post('/', [InvoiceController::class, 'store']);
        Route::get('/{id}', [InvoiceController::class, 'show']);
        Route::put('/{id}', [InvoiceController::class, 'update']);
        Route::delete('/{id}', [InvoiceController::class, 'destroy']);
        
        // Action routes
        Route::post('/{id}/send', [InvoiceController::class, 'send']);
        Route::post('/{id}/mark-as-paid', [InvoiceController::class, 'markAsPaid']);
    });

    // Invoice Item routes
    Route::prefix('invoice-items')->group(function () {
        Route::get('/', [InvoiceItemController::class, 'index']);
        Route::post('/', [InvoiceItemController::class, 'store']);
        Route::get('/{id}', [InvoiceItemController::class, 'show']);
        Route::put('/{id}', [InvoiceItemController::class, 'update']);
        Route::delete('/{id}', [InvoiceItemController::class, 'destroy']);
    });

    // Payment routes
    Route::prefix('payments')->group(function () {
        Route::get('/', [PaymentController::class, 'index']);
        Route::post('/', [PaymentController::class, 'store']);
        Route::get('/{id}', [PaymentController::class, 'show']);
        Route::put('/{id}', [PaymentController::class, 'update']);
        Route::delete('/{id}', [PaymentController::class, 'destroy']);
    });
});
