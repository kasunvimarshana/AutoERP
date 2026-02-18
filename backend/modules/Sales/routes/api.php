<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Sales\Http\Controllers\CustomerController;
use Modules\Sales\Http\Controllers\QuotationController;
use Modules\Sales\Http\Controllers\SalesOrderController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('sales')->name('sales.')->group(function () {

    // Customer Routes
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::post('/', [CustomerController::class, 'store'])->name('store');
        Route::get('/{id}', [CustomerController::class, 'show'])->name('show');
        Route::put('/{id}', [CustomerController::class, 'update'])->name('update');
        Route::delete('/{id}', [CustomerController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/statistics', [CustomerController::class, 'statistics'])->name('statistics');
        Route::post('/{id}/activate', [CustomerController::class, 'activate'])->name('activate');
        Route::post('/{id}/deactivate', [CustomerController::class, 'deactivate'])->name('deactivate');
    });

    // Quotation Routes
    Route::prefix('quotations')->name('quotations.')->group(function () {
        Route::get('/', [QuotationController::class, 'index'])->name('index');
        Route::post('/', [QuotationController::class, 'store'])->name('store');
        Route::get('/{id}', [QuotationController::class, 'show'])->name('show');
        Route::put('/{id}', [QuotationController::class, 'update'])->name('update');
        Route::delete('/{id}', [QuotationController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/send', [QuotationController::class, 'send'])->name('send');
        Route::post('/{id}/accept', [QuotationController::class, 'accept'])->name('accept');
        Route::post('/{id}/reject', [QuotationController::class, 'reject'])->name('reject');
        Route::post('/{id}/convert', [QuotationController::class, 'convert'])->name('convert');
    });

    // Sales Order Routes
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [SalesOrderController::class, 'index'])->name('index');
        Route::post('/', [SalesOrderController::class, 'store'])->name('store');
        Route::get('/{order}', [SalesOrderController::class, 'show'])->name('show');
        Route::put('/{order}', [SalesOrderController::class, 'update'])->name('update');
        Route::delete('/{order}', [SalesOrderController::class, 'destroy'])->name('destroy');
    });
});
