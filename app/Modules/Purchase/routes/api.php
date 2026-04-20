<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Purchase\Infrastructure\Http\Controllers\GrnController;
use Modules\Purchase\Infrastructure\Http\Controllers\PurchaseInvoiceController;
use Modules\Purchase\Infrastructure\Http\Controllers\PurchaseOrderController;
use Modules\Purchase\Infrastructure\Http\Controllers\PurchaseReturnController;

Route::middleware(['auth:api', 'resolve.tenant'])->group(function (): void {
    Route::apiResource('purchase-orders', PurchaseOrderController::class);
    Route::post('purchase-orders/{purchaseOrder}/confirm', [PurchaseOrderController::class, 'confirm']);

    Route::apiResource('grns', GrnController::class);
    Route::post('grns/{grn}/post', [GrnController::class, 'post']);

    Route::apiResource('purchase-invoices', PurchaseInvoiceController::class);
    Route::post('purchase-invoices/{invoice}/approve', [PurchaseInvoiceController::class, 'approve']);

    Route::apiResource('purchase-returns', PurchaseReturnController::class);
    Route::post('purchase-returns/{purchaseReturn}/post', [PurchaseReturnController::class, 'post']);
});
