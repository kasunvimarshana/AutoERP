<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

use Illuminate\Support\Facades\Route;
use Modules\Invoice\Constants\InvoicePermission;
use Modules\Invoice\Http\Controllers\InvoiceController;
use Modules\Invoice\Http\Controllers\InvoiceLayoutController;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit').':required',
    'tenant.feature:invoice',
];

$permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');
$requires = static fn (string $permission): string => $permissionMiddleware.':'.$permission;

Route::prefix('api/v1/invoices')->middleware($middleware)->name('api.v1.invoices.')->group(function () use ($requires): void {
    Route::get('/', [InvoiceController::class, 'index'])
        ->middleware($requires(InvoicePermission::VIEW))
        ->name('index');
    Route::post('preview', [InvoiceController::class, 'preview'])
        ->middleware($requires(InvoicePermission::PREVIEW))
        ->name('preview');
    Route::post('/', [InvoiceController::class, 'store'])
        ->middleware($requires(InvoicePermission::CREATE))
        ->name('store');
    Route::get('{invoice}', [InvoiceController::class, 'show'])
        ->whereNumber('invoice')
        ->middleware($requires(InvoicePermission::VIEW))
        ->name('show');
    Route::post('{invoice}/approve', [InvoiceController::class, 'approve'])
        ->whereNumber('invoice')
        ->middleware($requires(InvoicePermission::APPROVE))
        ->name('approve');
    Route::post('{invoice}/post', [InvoiceController::class, 'post'])
        ->whereNumber('invoice')
        ->middleware($requires(InvoicePermission::POST))
        ->name('post');
    Route::post('{invoice}/reverse', [InvoiceController::class, 'reverse'])
        ->whereNumber('invoice')
        ->middleware($requires(InvoicePermission::REVERSE))
        ->name('reverse');
    Route::post('{invoice}/cancel', [InvoiceController::class, 'cancel'])
        ->whereNumber('invoice')
        ->middleware($requires(InvoicePermission::CANCEL))
        ->name('cancel');
    Route::get('{invoice}/balance', [InvoiceController::class, 'balance'])
        ->whereNumber('invoice')
        ->middleware($requires(InvoicePermission::VIEW_BALANCE))
        ->name('balance');
    Route::get('{invoice}/sources', [InvoiceController::class, 'sources'])
        ->whereNumber('invoice')
        ->middleware($requires(InvoicePermission::VIEW_SOURCES))
        ->name('sources');
    Route::get('{invoice}/adjustments', [InvoiceController::class, 'adjustments'])
        ->whereNumber('invoice')
        ->middleware($requires(InvoicePermission::VIEW_SOURCES))
        ->name('adjustments');
    Route::post('{invoice}/signed-print', [InvoiceController::class, 'signedPrintLink'])
        ->whereNumber('invoice')
        ->middleware($requires(InvoicePermission::VIEW))
        ->name('signed-print');
    Route::post('{invoice}/whatsapp-share', [InvoiceController::class, 'whatsappShare'])
        ->whereNumber('invoice')
        ->middleware($requires(InvoicePermission::VIEW))
        ->name('whatsapp-share');
});

Route::prefix('api/v1/invoice-layouts')->middleware($middleware)->name('api.v1.invoice-layouts.')->group(function () use ($requires): void {
    Route::get('/', [InvoiceLayoutController::class, 'index'])
        ->middleware($requires(InvoicePermission::LAYOUTS_VIEW))
        ->name('index');
    Route::post('/', [InvoiceLayoutController::class, 'store'])
        ->middleware($requires(InvoicePermission::LAYOUTS_MANAGE))
        ->name('store');
    Route::post('set-default', [InvoiceLayoutController::class, 'setDefault'])
        ->middleware($requires(InvoicePermission::LAYOUTS_MANAGE))
        ->name('set-default');
    Route::get('printer-assignments', [InvoiceLayoutController::class, 'printerAssignments'])
        ->middleware($requires(InvoicePermission::LAYOUTS_VIEW))
        ->name('printer-assignments');
    Route::delete('printer-assignments/{printer}', [InvoiceLayoutController::class, 'deletePrinterAssignment'])
        ->middleware($requires(InvoicePermission::LAYOUTS_MANAGE))
        ->name('delete-printer-assignment');
    Route::get('{layout}', [InvoiceLayoutController::class, 'show'])
        ->whereNumber('layout')
        ->middleware($requires(InvoicePermission::LAYOUTS_VIEW))
        ->name('show');
    Route::match(['put', 'patch'], '{layout}', [InvoiceLayoutController::class, 'update'])
        ->whereNumber('layout')
        ->middleware($requires(InvoicePermission::LAYOUTS_MANAGE))
        ->name('update');
    Route::delete('{layout}', [InvoiceLayoutController::class, 'destroy'])
        ->whereNumber('layout')
        ->middleware($requires(InvoicePermission::LAYOUTS_MANAGE))
        ->name('destroy');
    Route::post('{layout}/preview', [InvoiceLayoutController::class, 'preview'])
        ->whereNumber('layout')
        ->middleware($requires(InvoicePermission::LAYOUTS_VIEW))
        ->name('preview');
});
