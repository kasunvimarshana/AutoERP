<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Sales\Interfaces\Http\Controllers\SalesController;

/*
|--------------------------------------------------------------------------
| Sales Module API Routes
|--------------------------------------------------------------------------
|
| All routes are versioned under /api/v1
|
*/

Route::middleware('auth:api')->prefix('api/v1')->name('sales.')->group(function (): void {
    Route::post('sales/orders', [SalesController::class, 'createOrder'])->name('orders.store');
    Route::get('sales/orders', [SalesController::class, 'listOrders'])->name('orders.index');
    Route::get('sales/orders/{id}', [SalesController::class, 'showOrder'])->name('orders.show');
    Route::post('sales/orders/{id}/confirm', [SalesController::class, 'confirmOrder'])->name('orders.confirm');
    Route::post('sales/orders/{id}/cancel', [SalesController::class, 'cancelOrder'])->name('orders.cancel');
    Route::get('sales/customers', [SalesController::class, 'listCustomers'])->name('customers.index');

    // Delivery endpoints
    Route::post('sales/orders/{id}/deliveries', [SalesController::class, 'createDelivery'])->name('deliveries.store');
    Route::get('sales/orders/{id}/deliveries', [SalesController::class, 'listDeliveries'])->name('deliveries.index');

    // Invoice endpoints
    Route::post('sales/orders/{id}/invoices', [SalesController::class, 'createInvoice'])->name('invoices.store');
    Route::get('sales/orders/{id}/invoices', [SalesController::class, 'listInvoices'])->name('invoices.index');
    Route::get('sales/invoices/{id}', [SalesController::class, 'showInvoice'])->name('invoices.show');

    // Returns endpoint — restores inventory quantities batch/lot-accurately
    Route::post('sales/orders/{id}/returns', [SalesController::class, 'createReturn'])->name('returns.store');
});
