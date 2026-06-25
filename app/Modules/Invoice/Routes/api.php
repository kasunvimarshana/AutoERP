<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Invoice\Http\Controllers\InvoiceController;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit'),
];

Route::prefix('api/v1/invoices')->middleware($middleware)->name('api.v1.invoices.')->group(function (): void {
    Route::get('/', [InvoiceController::class, 'index'])->name('index');
    Route::post('preview', [InvoiceController::class, 'preview'])->name('preview');
    Route::post('/', [InvoiceController::class, 'store'])->name('store');
    Route::get('{invoice}', [InvoiceController::class, 'show'])->whereNumber('invoice')->name('show');
    Route::post('{invoice}/approve', [InvoiceController::class, 'approve'])->whereNumber('invoice')->name('approve');
    Route::post('{invoice}/post', [InvoiceController::class, 'post'])->whereNumber('invoice')->name('post');
    Route::post('{invoice}/cancel', [InvoiceController::class, 'cancel'])->whereNumber('invoice')->name('cancel');
    Route::get('{invoice}/balance', [InvoiceController::class, 'balance'])->whereNumber('invoice')->name('balance');
    Route::get('{invoice}/sources', [InvoiceController::class, 'sources'])->whereNumber('invoice')->name('sources');
    Route::get('{invoice}/adjustments', [InvoiceController::class, 'adjustments'])->whereNumber('invoice')->name('adjustments');
});
