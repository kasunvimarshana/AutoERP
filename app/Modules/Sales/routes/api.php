<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Sales\Infrastructure\Http\Controllers\SalesInvoiceController;
use Modules\Sales\Infrastructure\Http\Controllers\SalesOrderController;
use Modules\Sales\Infrastructure\Http\Controllers\SalesReturnController;
use Modules\Sales\Infrastructure\Http\Controllers\ShipmentController;

Route::middleware(['auth.configured', 'resolve.tenant'])->group(function (): void {
    Route::apiResource('sales-orders', SalesOrderController::class);
    Route::post('sales-orders/{salesOrder}/confirm', [SalesOrderController::class, 'confirm']);
    Route::post('sales-orders/{salesOrder}/cancel', [SalesOrderController::class, 'cancel']);

    Route::apiResource('shipments', ShipmentController::class);
    Route::post('shipments/{shipment}/process', [ShipmentController::class, 'process']);

    Route::apiResource('sales-invoices', SalesInvoiceController::class);
    Route::post('sales-invoices/{salesInvoice}/post', [SalesInvoiceController::class, 'post']);
    Route::post('sales-invoices/{salesInvoice}/record-payment', [SalesInvoiceController::class, 'recordPayment']);
    Route::post('sales-invoices/{salesInvoice}/record-refund', [SalesInvoiceController::class, 'recordRefund']);

    Route::apiResource('sales-returns', SalesReturnController::class);
    Route::post('sales-returns/{salesReturn}/approve', [SalesReturnController::class, 'approve']);
    Route::post('sales-returns/{salesReturn}/receive', [SalesReturnController::class, 'receive']);
});
