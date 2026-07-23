<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Reporting\Http\Controllers\EmployeeCommissionReportController;
use Modules\Reporting\Http\Controllers\OperationalReportController;
use Modules\Reporting\Http\Controllers\ReportController;
use Modules\Reporting\Http\Controllers\TechnicianWorkReportController;
use Modules\Reporting\Http\Controllers\VehicleRentalReportController;
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
        Route::get('purchase/detailed', [OperationalReportController::class, 'detailedPurchase'])->name('purchase.detailed');
        Route::get('vehicle-service/detailed', [OperationalReportController::class, 'detailedVehicleService'])->name('vehicle-service.detailed');
        Route::get('vehicle-service/employee-incentives', [OperationalReportController::class, 'employeeIncentives'])->name('vehicle-service.employee-incentives');
        Route::get('vehicle-service/technician-work', [TechnicianWorkReportController::class, 'index'])->name('vehicle-service.technician-work');
        Route::get('vehicle-service/employee-commissions', [EmployeeCommissionReportController::class, 'index'])
            ->name('vehicle-service.employee-commissions');

        Route::middleware('tenant.feature:vehicle-rental')->group(function (): void {
            Route::get('vehicle-rental/running-chart', [VehicleRentalReportController::class, 'runningChart'])->name('vehicle-rental.running-chart');
            Route::get('vehicle-rental/chart-exceptions', [VehicleRentalReportController::class, 'chartExceptions'])->name('vehicle-rental.chart-exceptions');
            Route::get('vehicle-rental/customer-invoices', [VehicleRentalReportController::class, 'customerInvoices'])->name('vehicle-rental.customer-invoices');
            Route::get('vehicle-rental/owner-vouchers', [VehicleRentalReportController::class, 'ownerVouchers'])->name('vehicle-rental.owner-vouchers');
            Route::get('vehicle-rental/rental-history', [VehicleRentalReportController::class, 'rentalHistory'])->name('vehicle-rental.rental-history');
        });

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

        Route::middleware('tenant.feature:vehicle-rental')->group(function () use ($exportFormats): void {
            Route::get('vehicle-rental/running-chart/export/{format}', [VehicleRentalReportController::class, 'exportRunningChart'])
                ->whereIn('format', $exportFormats)
                ->name('vehicle-rental.running-chart.export');
            Route::get('vehicle-rental/chart-exceptions/export/{format}', [VehicleRentalReportController::class, 'exportChartExceptions'])
                ->whereIn('format', $exportFormats)
                ->name('vehicle-rental.chart-exceptions.export');
            Route::get('vehicle-rental/customer-invoices/export/{format}', [VehicleRentalReportController::class, 'exportCustomerInvoices'])
                ->whereIn('format', $exportFormats)
                ->name('vehicle-rental.customer-invoices.export');
            Route::get('vehicle-rental/owner-vouchers/export/{format}', [VehicleRentalReportController::class, 'exportOwnerVouchers'])
                ->whereIn('format', $exportFormats)
                ->name('vehicle-rental.owner-vouchers.export');
            Route::get('vehicle-rental/rental-history/export/{format}', [VehicleRentalReportController::class, 'exportRentalHistory'])
                ->whereIn('format', $exportFormats)
                ->name('vehicle-rental.rental-history.export');
        });

        Route::get('{report}/export/{format}', [ReportController::class, 'export'])
            ->where('report', '[A-Za-z0-9._-]+')
            ->whereIn('format', $exportFormats)
            ->name('export');
    });
});
