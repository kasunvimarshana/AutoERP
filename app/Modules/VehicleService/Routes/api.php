<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\VehicleService\Constants\VehicleServicePermission;
use Modules\VehicleService\Enums\VehicleServiceWorkforceRole;
use Modules\VehicleService\Http\Controllers\VehicleServiceCommissionPolicyController;
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
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit').':required',
    'tenant.feature:vehicle-service',
];
$permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');
$requires = static fn (string $permission): string => $permissionMiddleware.':'.$permission;
$workforceRolePattern = implode('|', VehicleServiceWorkforceRole::values());

Route::prefix('api/v1/vehicle-service')->middleware($middleware)->name('api.v1.vehicle-service.')->group(function () use ($requires, $workforceRolePattern): void {
    Route::middleware($requires(VehicleServicePermission::JOBS_VIEW))->group(function (): void {
        Route::get('jobs/lookup', [VehicleServiceJobController::class, 'lookup'])->name('jobs.lookup');
        Route::get('jobs', [VehicleServiceJobController::class, 'index'])->name('jobs.index');
        Route::get('jobs/{job}', [VehicleServiceJobController::class, 'show'])->whereNumber('job')->name('jobs.show');
        Route::get('jobs/{job}/inspection', [VehicleServiceJobController::class, 'inspection'])->whereNumber('job')->name('inspection.show');
        Route::get('jobs/{job}/status-history', [VehicleServiceJobController::class, 'statusHistory'])->whereNumber('job')->name('status-history.index');
    });
    Route::post('jobs', [VehicleServiceJobController::class, 'store'])->middleware($requires(VehicleServicePermission::JOBS_CREATE))->name('jobs.store');
    Route::middleware($requires(VehicleServicePermission::JOBS_UPDATE))->group(function (): void {
        Route::put('jobs/{job}', [VehicleServiceJobController::class, 'update'])->whereNumber('job')->name('jobs.update');
        Route::delete('jobs/{job}', [VehicleServiceJobController::class, 'destroy'])->whereNumber('job')->name('jobs.destroy');
        Route::put('jobs/{job}/inspection', [VehicleServiceJobController::class, 'updateInspection'])->whereNumber('job')->name('inspection.update');
    });
    Route::middleware($requires(VehicleServicePermission::JOBS_TRANSITION))->group(function (): void {
        Route::patch('jobs/{job}/inspect', [VehicleServiceJobController::class, 'inspect'])->whereNumber('job')->name('jobs.inspect');
        Route::patch('jobs/{job}/start', [VehicleServiceJobController::class, 'start'])->whereNumber('job')->name('jobs.start');
        Route::patch('jobs/{job}/complete', [VehicleServiceJobController::class, 'complete'])->whereNumber('job')->name('jobs.complete');
        Route::patch('jobs/{job}/cancel', [VehicleServiceJobController::class, 'cancel'])->whereNumber('job')->name('jobs.cancel');
    });

    Route::middleware($requires(VehicleServicePermission::LINES_VIEW))->group(function (): void {
        Route::get('jobs/{job}/lines', [VehicleServiceLineController::class, 'index'])->whereNumber('job')->name('lines.index');
        Route::get('jobs/{job}/billable-lines', [VehicleServiceInvoiceController::class, 'lines'])->whereNumber('job')->name('billable-lines');
    });
    Route::middleware($requires(VehicleServicePermission::LINES_MANAGE))->group(function (): void {
        Route::post('jobs/{job}/lines', [VehicleServiceLineController::class, 'store'])->whereNumber('job')->name('lines.store');
        Route::put('jobs/{job}/lines/{line}', [VehicleServiceLineController::class, 'update'])->whereNumber('job')->whereNumber('line')->name('lines.update');
        Route::delete('jobs/{job}/lines/{line}', [VehicleServiceLineController::class, 'destroy'])->whereNumber('job')->whereNumber('line')->name('lines.destroy');
    });

    Route::middleware($requires(VehicleServicePermission::WORKFORCE_VIEW))->group(function (): void {
        Route::get('jobs/{job}/lines/{line}/employees', [VehicleServiceWorkforceController::class, 'index'])->whereNumber('job')->whereNumber('line')->name('employees.index');
        Route::get('jobs/{job}/employee-assignable-lines', [VehicleServiceWorkforceController::class, 'assignableLines'])->whereNumber('job')->name('employee-assignable-lines');
    });
    Route::middleware($requires(VehicleServicePermission::WORKFORCE_MANAGE))->group(function (): void {
        Route::post('jobs/{job}/lines/{line}/employees', [VehicleServiceWorkforceController::class, 'store'])->whereNumber('job')->whereNumber('line')->name('employees.store');
        Route::put('jobs/{job}/lines/{line}/employees/{assignment}', [VehicleServiceWorkforceController::class, 'update'])->whereNumber('job')->whereNumber('line')->whereNumber('assignment')->name('employees.update');
        Route::delete('jobs/{job}/lines/{line}/employees/{assignment}', [VehicleServiceWorkforceController::class, 'destroy'])->whereNumber('job')->whereNumber('line')->whereNumber('assignment')->name('employees.destroy');
    });

    Route::middleware($requires(VehicleServicePermission::COMMISSIONS_VIEW))->group(function () use ($workforceRolePattern): void {
        Route::get('commission-policies/supervisor-default', [VehicleServiceCommissionPolicyController::class, 'supervisorDefault'])->name('commission-policies.supervisor-default.show');
        Route::get('commission-policies/labor-items/{item}/{role}', [VehicleServiceCommissionPolicyController::class, 'laborItemRule'])
            ->whereNumber('item')->where('role', $workforceRolePattern)->name('commission-policies.labor-items.show');
    });
    Route::middleware($requires(VehicleServicePermission::COMMISSIONS_MANAGE))->group(function () use ($workforceRolePattern): void {
        Route::put('commission-policies/supervisor-default', [VehicleServiceCommissionPolicyController::class, 'saveSupervisorDefault'])->name('commission-policies.supervisor-default.update');
        Route::put('commission-policies/labor-items/{item}/{role}', [VehicleServiceCommissionPolicyController::class, 'saveLaborItemRule'])
            ->whereNumber('item')->where('role', $workforceRolePattern)->name('commission-policies.labor-items.update');
    });

    Route::get('jobs/{job}/inventory-issue-lines', [VehicleServiceInventoryController::class, 'lines'])->whereNumber('job')->middleware($requires(VehicleServicePermission::INVENTORY_VIEW))->name('inventory-issue-lines');
    Route::post('jobs/{job}/issue-inventory', [VehicleServiceInventoryController::class, 'issue'])->whereNumber('job')->middleware($requires(VehicleServicePermission::INVENTORY_ISSUE))->name('inventory.issue');

    Route::post('jobs/{job}/invoices/preview', [VehicleServiceInvoiceController::class, 'preview'])->whereNumber('job')->middleware($requires(VehicleServicePermission::INVOICES_VIEW))->name('invoices.preview');
    Route::post('jobs/{job}/invoices', [VehicleServiceInvoiceController::class, 'store'])->whereNumber('job')->middleware($requires(VehicleServicePermission::INVOICES_CREATE))->name('invoices.store');

    Route::middleware($requires(VehicleServicePermission::PAYMENTS_VIEW))->group(function (): void {
        Route::get('jobs/{job}/payments/options', [VehicleServicePaymentController::class, 'options'])->whereNumber('job')->name('payments.options');
        Route::post('jobs/{job}/payments/prepare', [VehicleServicePaymentController::class, 'prepare'])->whereNumber('job')->name('payments.prepare');
    });
    Route::post('jobs/{job}/payments', [VehicleServicePaymentController::class, 'store'])->whereNumber('job')->middleware($requires(VehicleServicePermission::PAYMENTS_CREATE))->name('payments.store');

    Route::middleware($requires(VehicleServicePermission::DOCUMENTS_VIEW))->group(function (): void {
        Route::get('jobs/{job}/documents/options', [VehicleServiceDocumentController::class, 'options'])->whereNumber('job')->name('documents.options');
        Route::get('jobs/{job}/documents', [VehicleServiceDocumentController::class, 'index'])->whereNumber('job')->name('documents.index');
        Route::get('jobs/{job}/documents/{document}/download', [VehicleServiceDocumentController::class, 'download'])->whereNumber('job')->whereNumber('document')->name('documents.download');
    });
    Route::middleware($requires(VehicleServicePermission::DOCUMENTS_MANAGE))->group(function (): void {
        Route::post('jobs/{job}/documents', [VehicleServiceDocumentController::class, 'store'])->whereNumber('job')->name('documents.store');
        Route::delete('jobs/{job}/documents/{document}', [VehicleServiceDocumentController::class, 'destroy'])->whereNumber('job')->whereNumber('document')->name('documents.destroy');
    });
});
