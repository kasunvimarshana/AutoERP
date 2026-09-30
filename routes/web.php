<?php

use Illuminate\Support\Facades\Route;
use Modules\Invoice\Http\Controllers\InvoiceController;
use Modules\Purchase\Http\Controllers\PurchaseOrderController;

Route::get('/', function () {
    return view('welcome');
});

$__printMiddleware = [
    'web',
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit').':required',
    'tenant.feature:invoice',
];

Route::middleware($__printMiddleware)->group(function () {
    Route::get('/invoice/print/{invoice}', [InvoiceController::class, 'printView'])->name('invoice.print');
    Route::get('/invoice/pdf/{invoice}', [InvoiceController::class, 'pdf'])->name('invoice.pdf');
    Route::get('/invoices/{invoice}/print', [InvoiceController::class, 'printView'])->name('invoices.print');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
});

Route::get('/signed/invoices/{invoice}/print/{tenant}', [InvoiceController::class, 'publicPrint'])
    ->name('invoices.public.print')
    ->middleware('signed');

Route::get('/signed/invoices/{invoice}/pdf/{tenant}', [InvoiceController::class, 'publicPdf'])
    ->name('invoices.public.pdf')
    ->middleware('signed');
Route::get('/shared/invoices/{invoice}/pdf/{tenant}', [InvoiceController::class, 'publicSharedPdf'])
    ->name('invoices.public.shared-pdf')
    ->middleware('signed');

Route::get('/shared/purchase-orders/{order}/pdf/{tenant}', [PurchaseOrderController::class, 'publicSharedPdf'])
    ->name('purchase-orders.public.shared-pdf')
    ->middleware('signed');

Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '^(?!api).*$');
