<?php

use Illuminate\Support\Facades\Route;
use Modules\POS\Http\Controllers\BusinessLocationController;
use Modules\POS\Http\Controllers\CashRegisterController;
use Modules\POS\Http\Controllers\TransactionController;
use Modules\POS\Http\Controllers\POSController;

Route::prefix('locations')->group(function () {
    Route::get('/', [BusinessLocationController::class, 'index']);
    Route::post('/', [BusinessLocationController::class, 'store']);
    Route::get('/active', [BusinessLocationController::class, 'active']);
    Route::get('/{id}', [BusinessLocationController::class, 'show']);
    Route::put('/{id}', [BusinessLocationController::class, 'update']);
    Route::delete('/{id}', [BusinessLocationController::class, 'destroy']);
});

Route::prefix('cash-registers')->group(function () {
    Route::get('/', [CashRegisterController::class, 'index']);
    Route::post('/', [CashRegisterController::class, 'store']);
    Route::get('/{id}', [CashRegisterController::class, 'show']);
    Route::post('/{id}/open', [CashRegisterController::class, 'open']);
    Route::post('/{id}/close', [CashRegisterController::class, 'close']);
    Route::get('/{id}/balance', [CashRegisterController::class, 'currentBalance']);
});

Route::prefix('transactions')->group(function () {
    Route::get('/', [TransactionController::class, 'index']);
    Route::post('/', [TransactionController::class, 'store']);
    Route::get('/{id}', [TransactionController::class, 'show']);
    Route::put('/{id}', [TransactionController::class, 'update']);
    Route::delete('/{id}', [TransactionController::class, 'destroy']);
    Route::post('/{id}/complete', [TransactionController::class, 'complete']);
    Route::post('/{id}/cancel', [TransactionController::class, 'cancel']);
    Route::post('/{id}/payments', [TransactionController::class, 'addPayment']);
    
    // Receipt endpoints
    Route::get('/{id}/receipt', [POSController::class, 'getReceipt']);
    Route::post('/{id}/print', [POSController::class, 'printReceipt']);
    Route::post('/{id}/email-receipt', [POSController::class, 'emailReceipt']);
    
    // Return endpoints
    Route::post('/{id}/return', [POSController::class, 'processReturn']);
    Route::post('/{id}/full-return', [POSController::class, 'processFullReturn']);
});

// POS-specific endpoints
Route::prefix('pos')->group(function () {
    // Checkout
    Route::post('/checkout', [POSController::class, 'checkout']);
    Route::post('/quick-sale', [POSController::class, 'quickSale']);
    
    // Suspended sales
    Route::post('/sales/suspend', [POSController::class, 'suspendSale']);
    Route::get('/sales/suspended', [POSController::class, 'getSuspendedSales']);
    Route::post('/sales/{id}/resume', [POSController::class, 'resumeSale']);
    Route::post('/sales/{id}/complete', [POSController::class, 'completeSuspendedSale']);
    Route::delete('/sales/{id}/cancel', [POSController::class, 'cancelSuspendedSale']);
});
