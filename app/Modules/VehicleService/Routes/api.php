<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\VehicleService\Http\Controllers\VehicleServiceController;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit'),
];

Route::prefix('api/v1/vehicle-service')->middleware($middleware)->name('api.v1.vehicle-service.')->group(function (): void {
    Route::get('jobs/lookup', [VehicleServiceController::class, 'lookup'])->name('jobs.lookup');
    Route::get('jobs', [VehicleServiceController::class, 'index'])->name('jobs.index');
    Route::post('jobs', [VehicleServiceController::class, 'store'])->name('jobs.store');
    Route::get('jobs/{job}', [VehicleServiceController::class, 'show'])->whereNumber('job')->name('jobs.show');
    Route::put('jobs/{job}', [VehicleServiceController::class, 'update'])->whereNumber('job')->name('jobs.update');
    Route::delete('jobs/{job}', [VehicleServiceController::class, 'destroy'])->whereNumber('job')->name('jobs.destroy');

    Route::patch('jobs/{job}/inspect', [VehicleServiceController::class, 'inspect'])->whereNumber('job')->name('jobs.inspect');
    Route::patch('jobs/{job}/start', [VehicleServiceController::class, 'start'])->whereNumber('job')->name('jobs.start');
    Route::patch('jobs/{job}/complete', [VehicleServiceController::class, 'complete'])->whereNumber('job')->name('jobs.complete');
    Route::patch('jobs/{job}/cancel', [VehicleServiceController::class, 'cancel'])->whereNumber('job')->name('jobs.cancel');

    Route::get('jobs/{job}/inspection', [VehicleServiceController::class, 'inspection'])->whereNumber('job')->name('inspection.show');
    Route::put('jobs/{job}/inspection', [VehicleServiceController::class, 'updateInspection'])->whereNumber('job')->name('inspection.update');

    Route::get('jobs/{job}/lines', [VehicleServiceController::class, 'lines'])->whereNumber('job')->name('lines.index');
    Route::post('jobs/{job}/lines', [VehicleServiceController::class, 'storeLine'])->whereNumber('job')->name('lines.store');
    Route::put('jobs/{job}/lines/{line}', [VehicleServiceController::class, 'updateLine'])->whereNumber(['job', 'line'])->name('lines.update');
    Route::delete('jobs/{job}/lines/{line}', [VehicleServiceController::class, 'destroyLine'])->whereNumber(['job', 'line'])->name('lines.destroy');

    Route::get('jobs/{job}/lines/{line}/employees', [VehicleServiceController::class, 'employees'])->whereNumber(['job', 'line'])->name('employees.index');
    Route::post('jobs/{job}/lines/{line}/employees', [VehicleServiceController::class, 'storeEmployee'])->whereNumber(['job', 'line'])->name('employees.store');
    Route::put('jobs/{job}/lines/{line}/employees/{assignment}', [VehicleServiceController::class, 'updateEmployee'])->whereNumber(['job', 'line', 'assignment'])->name('employees.update');
    Route::delete('jobs/{job}/lines/{line}/employees/{assignment}', [VehicleServiceController::class, 'destroyEmployee'])->whereNumber(['job', 'line', 'assignment'])->name('employees.destroy');

    Route::post('jobs/{job}/issue-inventory', [VehicleServiceController::class, 'issueInventory'])->whereNumber('job')->name('inventory.issue');
    Route::post('jobs/{job}/invoices/preview', [VehicleServiceController::class, 'previewInvoice'])->whereNumber('job')->name('invoices.preview');
    Route::post('jobs/{job}/invoices', [VehicleServiceController::class, 'createInvoice'])->whereNumber('job')->name('invoices.store');
    Route::post('jobs/{job}/payments/prepare', [VehicleServiceController::class, 'preparePayment'])->whereNumber('job')->name('payments.prepare');
    Route::post('jobs/{job}/payments', [VehicleServiceController::class, 'createPayment'])->whereNumber('job')->name('payments.store');

    Route::get('jobs/{job}/documents', [VehicleServiceController::class, 'documents'])->whereNumber('job')->name('documents.index');
    Route::post('jobs/{job}/documents', [VehicleServiceController::class, 'storeDocument'])->whereNumber('job')->name('documents.store');
    Route::delete('jobs/{job}/documents/{document}', [VehicleServiceController::class, 'destroyDocument'])->whereNumber(['job', 'document'])->name('documents.destroy');
    Route::get('jobs/{job}/status-history', [VehicleServiceController::class, 'statusHistory'])->whereNumber('job')->name('status-history.index');

    Route::get('jobs/{job}/billable-lines', [VehicleServiceController::class, 'billableLines'])->whereNumber('job')->name('billable-lines');
    Route::get('jobs/{job}/inventory-issue-lines', [VehicleServiceController::class, 'inventoryIssueLines'])->whereNumber('job')->name('inventory-issue-lines');
    Route::get('jobs/{job}/employee-assignable-lines', [VehicleServiceController::class, 'employeeAssignableLines'])->whereNumber('job')->name('employee-assignable-lines');
});
