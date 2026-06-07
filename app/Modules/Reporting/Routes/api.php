<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Reporting\Http\Controllers\EmployeeCommissionReportController;
use Modules\Reporting\Http\Controllers\ReportController;
use Modules\Reporting\Http\Controllers\TechnicianWorkReportController;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit'),
];

Route::prefix('api/v1/reports')->middleware($middleware)->name('api.v1.reports.')->group(function (): void {
    Route::get('/', [ReportController::class, 'index'])->name('index');
    Route::get('vehicle-service/technician-work', [TechnicianWorkReportController::class, 'index'])->name('vehicle-service.technician-work');
    Route::get('vehicle-service/technician-work/export/{format}', [TechnicianWorkReportController::class, 'export'])
        ->whereIn('format', ['csv', 'xlsx', 'pdf', 'print'])
        ->name('vehicle-service.technician-work.export');
    Route::get('vehicle-service/employee-commissions', [EmployeeCommissionReportController::class, 'index'])
        ->name('vehicle-service.employee-commissions');
    Route::get('vehicle-service/employee-commissions/export/{format}', [EmployeeCommissionReportController::class, 'export'])
        ->whereIn('format', ['csv', 'xlsx', 'pdf', 'print'])
        ->name('vehicle-service.employee-commissions.export');
    Route::get('{report}', [ReportController::class, 'show'])->where('report', '[A-Za-z0-9._-]+')->name('show');
    Route::get('{report}/run', [ReportController::class, 'run'])->where('report', '[A-Za-z0-9._-]+')->name('run');
    Route::get('{report}/export/{format}', [ReportController::class, 'export'])
        ->where('report', '[A-Za-z0-9._-]+')
        ->whereIn('format', ['csv', 'xlsx', 'pdf', 'print'])
        ->name('export');
});
