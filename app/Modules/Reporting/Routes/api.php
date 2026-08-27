<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Reporting\Http\Controllers\EmployeeCommissionReportController;
use Modules\Reporting\Http\Controllers\OperationalReportController;
use Modules\Reporting\Http\Controllers\ReportController;
use Modules\Reporting\Http\Controllers\TechnicianWorkReportController;
use Modules\Reporting\Services\ReportingAuthorizationService;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit'),
    'tenant.feature:reporting',
];
$permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');
$requires = static fn (string $permission): string => $permissionMiddleware.':'.$permission;
$exportFormats = ['html', 'csv', 'xlsx', 'pdf', 'print'];

Route::prefix('api/v1/reports')->middleware($middleware)->name('api.v1.reports.')->group(function () use ($requires, $exportFormats): void {
    Route::middleware($requires(ReportingAuthorizationService::REPORTS_VIEW))->group(function (): void {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('summary', [OperationalReportController::class, 'summary'])->name('summary');
        Route::get('purchase/detailed', [OperationalReportController::class, 'detailedPurchase'])->name('purchase.detailed');
        Route::get('vehicle-service/detailed', [OperationalReportController::class, 'detailedVehicleService'])->name('vehicle-service.detailed');
        Route::get('vehicle-service/employee-incentives', [OperationalReportController::class, 'employeeIncentives'])->name('vehicle-service.employee-incentives');
        Route::get('vehicle-service/technician-work', [TechnicianWorkReportController::class, 'index'])->name('vehicle-service.technician-work');
        Route::get('vehicle-service/employee-commissions', [EmployeeCommissionReportController::class, 'index'])
            ->name('vehicle-service.employee-commissions');

        Route::get('{report}', [ReportController::class, 'show'])->where('report', '[A-Za-z0-9._-]+')->name('show');
        Route::get('{report}/run', [ReportController::class, 'run'])->where('report', '[A-Za-z0-9._-]+')->name('run');
    });

    Route::middleware($requires(ReportingAuthorizationService::REPORTS_EXPORT))->group(function () use ($exportFormats): void {
        Route::get('purchase/detailed/export/{format}', [OperationalReportController::class, 'exportDetailedPurchase'])
            ->whereIn('format', $exportFormats)
            ->name('purchase.detailed.export');
        Route::get('vehicle-service/detailed/export/{format}', [OperationalReportController::class, 'exportDetailedVehicleService'])
            ->whereIn('format', $exportFormats)
            ->name('vehicle-service.detailed.export');
        Route::get('vehicle-service/employee-incentives/export/{format}', [OperationalReportController::class, 'exportEmployeeIncentives'])
            ->whereIn('format', $exportFormats)
            ->name('vehicle-service.employee-incentives.export');
        Route::get('vehicle-service/technician-work/export/{format}', [TechnicianWorkReportController::class, 'export'])
            ->whereIn('format', $exportFormats)
            ->name('vehicle-service.technician-work.export');
        Route::get('vehicle-service/employee-commissions/export/{format}', [EmployeeCommissionReportController::class, 'export'])
            ->whereIn('format', $exportFormats)
            ->name('vehicle-service.employee-commissions.export');

        Route::get('{report}/export/{format}', [ReportController::class, 'export'])
            ->where('report', '[A-Za-z0-9._-]+')
            ->whereIn('format', $exportFormats)
            ->name('export');
    });
});
