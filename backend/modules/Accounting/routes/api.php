<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Accounting\Http\Controllers\AccountController;
use Modules\Accounting\Http\Controllers\InvoiceController;
use Modules\Accounting\Http\Controllers\JournalEntryController;
use Modules\Accounting\Http\Controllers\PaymentController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('accounting')->name('accounting.')->group(function () {

    // Chart of Accounts endpoints
    Route::controller(AccountController::class)->prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/', 'index')->name('list');
        Route::get('/tree', 'tree')->name('hierarchy');
        Route::post('/', 'store')->name('add');
        Route::get('/{id}', 'show')->name('view');
        Route::put('/{id}', 'update')->name('edit');
        Route::delete('/{id}', 'destroy')->name('remove');
    });

    // Journal Entry endpoints
    Route::controller(JournalEntryController::class)->prefix('journal-entries')->name('journal.')->group(function () {
        Route::get('/', 'index')->name('list');
        Route::post('/', 'store')->name('add');
        Route::get('/{id}', 'show')->name('view');
        Route::put('/{id}', 'update')->name('edit');
        Route::post('/{id}/post', 'post')->name('post');
        Route::delete('/{id}', 'destroy')->name('remove');
    });

    // Invoice endpoints
    Route::controller(InvoiceController::class)->prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', 'index')->name('list');
        Route::post('/', 'store')->name('add');
        Route::post('/from-order/{orderId}', 'generateFromOrder')->name('generate');
        Route::get('/{id}', 'show')->name('view');
        Route::put('/{id}', 'update')->name('edit');
        Route::post('/{id}/send', 'send')->name('send');
        Route::post('/{id}/mark-paid', 'markAsPaid')->name('mark_paid');
        Route::delete('/{id}', 'destroy')->name('remove');
    });

    // Payment endpoints
    Route::controller(PaymentController::class)->prefix('payments')->name('payments.')->group(function () {
        Route::get('/', 'index')->name('list');
        Route::post('/', 'store')->name('add');
        Route::get('/{id}', 'show')->name('view');
        Route::put('/{id}', 'update')->name('edit');
        Route::post('/{id}/allocate', 'allocate')->name('allocate');
        Route::post('/{id}/complete', 'complete')->name('complete');
        Route::post('/{id}/cancel', 'cancel')->name('cancel');
        Route::delete('/{id}', 'destroy')->name('remove');
    });
});
