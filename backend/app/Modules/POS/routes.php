<?php

use Illuminate\Support\Facades\Route;
use App\Modules\POS\Http\Controllers\POSTransactionController;
use App\Modules\POS\Http\Controllers\POSTransactionItemController;

Route::prefix('api/pos')->middleware(['api'])->group(function () {
    // POS Transaction routes
    Route::prefix('transactions')->group(function () {
        Route::get('/', [POSTransactionController::class, 'index']);
        Route::post('/', [POSTransactionController::class, 'store']);
        Route::get('/{id}', [POSTransactionController::class, 'show']);
        Route::put('/{id}', [POSTransactionController::class, 'update']);
        Route::delete('/{id}', [POSTransactionController::class, 'destroy']);
        
        // Action routes
        Route::post('/{id}/complete', [POSTransactionController::class, 'complete']);
        Route::post('/{id}/cancel', [POSTransactionController::class, 'cancel']);
        Route::post('/{id}/refund', [POSTransactionController::class, 'refund']);
        
        // Query routes
        Route::get('/status/filter', [POSTransactionController::class, 'getByStatus']);
        Route::get('/customer/{customerId}', [POSTransactionController::class, 'getByCustomer']);
        Route::get('/cashier/{cashierId}', [POSTransactionController::class, 'getByCashier']);
        Route::get('/date-range/filter', [POSTransactionController::class, 'getByDateRange']);
        Route::get('/sales/total', [POSTransactionController::class, 'getTotalSales']);
        Route::get('/sales/payment-method', [POSTransactionController::class, 'getSalesByPaymentMethod']);
    });

    // POS Transaction Item routes
    Route::prefix('transaction-items')->group(function () {
        Route::get('/', [POSTransactionItemController::class, 'index']);
        Route::post('/', [POSTransactionItemController::class, 'store']);
        Route::get('/{id}', [POSTransactionItemController::class, 'show']);
        Route::put('/{id}', [POSTransactionItemController::class, 'update']);
        Route::delete('/{id}', [POSTransactionItemController::class, 'destroy']);
        
        // Query routes
        Route::get('/transaction/{transactionId}', [POSTransactionItemController::class, 'getByTransaction']);
        Route::get('/sku/{skuId}', [POSTransactionItemController::class, 'getBySKU']);
        Route::get('/analytics/top-selling', [POSTransactionItemController::class, 'getTopSellingProducts']);
    });
});
