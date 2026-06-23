<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\VehicleService\Http\Controllers\VehicleServiceDocumentController;
use Modules\VehicleService\Http\Controllers\VehicleServiceInventoryController;
use Modules\VehicleService\Http\Controllers\VehicleServiceInvoiceController;
use Modules\VehicleService\Http\Controllers\VehicleServiceJobController;
use Modules\VehicleService\Http\Controllers\VehicleServiceLineController;
use Modules\VehicleService\Http\Controllers\VehicleServicePaymentController;
use Modules\VehicleService\Http\Controllers\VehicleServiceWorkforceController;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit'),
    'tenant.feature:vehicle-service',
];

Route::prefix('api/v1/vehicle-service')->middleware($middleware)->name('api.v1.vehicle-service.')->group(function (): void {
    Route::get('jobs/lookup', [VehicleServiceJobController::class, 'lookup'])->name('jobs.lookup');
    Route::get('jobs', [VehicleServiceJobController::class, 'index'])->name('jobs.index');
    Route::post('jobs', [VehicleServiceJobController::class, 'store'])->name('jobs.store');
    Route::get('jobs/{job}', [VehicleServiceJobController::class, 'show'])->whereNumber('job')->name('jobs.show');
    Route::put('jobs/{job}', [VehicleServiceJobController::class, 'update'])->whereNumber('job')->name('jobs.update');
    Route::delete('jobs/{job}', [VehicleServiceJobController::class, 'destroy'])->whereNumber('job')->name('jobs.destroy');

    Route::patch('jobs/{job}/inspect', [VehicleServiceJobController::class, 'inspect'])->whereNumber('job')->name('jobs.inspect');
    Route::patch('jobs/{job}/start', [VehicleServiceJobController::class, 'start'])->whereNumber('job')->name('jobs.start');
    Route::patch('jobs/{job}/complete', [VehicleServiceJobController::class, 'complete'])->whereNumber('job')->name('jobs.complete');
    Route::patch('jobs/{job}/cancel', [VehicleServiceJobController::class, 'cancel'])->whereNumber('job')->name('jobs.cancel');

    Route::get('jobs/{job}/inspection', [VehicleServiceJobController::class, 'inspection'])->whereNumber('job')->name('inspection.show');
    Route::put('jobs/{job}/inspection', [VehicleServiceJobController::class, 'updateInspection'])->whereNumber('job')->name('inspection.update');

    Route::get('jobs/{job}/lines', [VehicleServiceLineController::class, 'index'])->whereNumber('job')->name('lines.index');
    Route::post('jobs/{job}/lines', [VehicleServiceLineController::class, 'store'])->whereNumber('job')->name('lines.store');
    Route::put('jobs/{job}/lines/{line}', [VehicleServiceLineController::class, 'update'])->whereNumber(['job', 'line'])->name('lines.update');
    Route::delete('jobs/{job}/lines/{line}', [VehicleServiceLineController::class, 'destroy'])->whereNumber(['job', 'line'])->name('lines.destroy');

    Route::get('jobs/{job}/lines/{line}/employees', [VehicleServiceWorkforceController::class, 'index'])->whereNumber(['job', 'line'])->name('employees.index');
    Route::post('jobs/{job}/lines/{line}/employees', [VehicleServiceWorkforceController::class, 'store'])->whereNumber(['job', 'line'])->name('employees.store');
    Route::put('jobs/{job}/lines/{line}/employees/{assignment}', [VehicleServiceWorkforceController::class, 'update'])->whereNumber(['job', 'line', 'assignment'])->name('employees.update');
    Route::delete('jobs/{job}/lines/{line}/employees/{assignment}', [VehicleServiceWorkforceController::class, 'destroy'])->whereNumber(['job', 'line', 'assignment'])->name('employees.destroy');

    Route::post('jobs/{job}/issue-inventory', [VehicleServiceInventoryController::class, 'issue'])->whereNumber('job')->name('inventory.issue');
    Route::post('jobs/{job}/invoices/preview', [VehicleServiceInvoiceController::class, 'preview'])->whereNumber('job')->name('invoices.preview');
    Route::post('jobs/{job}/invoices', [VehicleServiceInvoiceController::class, 'store'])->whereNumber('job')->name('invoices.store');
    Route::get('jobs/{job}/payments/options', [VehicleServicePaymentController::class, 'options'])->whereNumber('job')->name('payments.options');
    Route::post('jobs/{job}/payments/prepare', [VehicleServicePaymentController::class, 'prepare'])->whereNumber('job')->name('payments.prepare');
    Route::post('jobs/{job}/payments', [VehicleServicePaymentController::class, 'store'])->whereNumber('job')->name('payments.store');

    Route::get('jobs/{job}/documents/options', [VehicleServiceDocumentController::class, 'options'])->whereNumber('job')->name('documents.options');
    Route::get('jobs/{job}/documents', [VehicleServiceDocumentController::class, 'index'])->whereNumber('job')->name('documents.index');
    Route::post('jobs/{job}/documents', [VehicleServiceDocumentController::class, 'store'])->whereNumber('job')->name('documents.store');
    Route::get('jobs/{job}/documents/{document}/download', [VehicleServiceDocumentController::class, 'download'])->whereNumber(['job', 'document'])->name('documents.download');
    Route::delete('jobs/{job}/documents/{document}', [VehicleServiceDocumentController::class, 'destroy'])->whereNumber(['job', 'document'])->name('documents.destroy');
    Route::get('jobs/{job}/status-history', [VehicleServiceJobController::class, 'statusHistory'])->whereNumber('job')->name('status-history.index');

    Route::get('jobs/{job}/billable-lines', [VehicleServiceInvoiceController::class, 'lines'])->whereNumber('job')->name('billable-lines');
    Route::get('jobs/{job}/inventory-issue-lines', [VehicleServiceInventoryController::class, 'lines'])->whereNumber('job')->name('inventory-issue-lines');
    Route::get('jobs/{job}/employee-assignable-lines', [VehicleServiceWorkforceController::class, 'assignableLines'])->whereNumber('job')->name('employee-assignable-lines');
});
