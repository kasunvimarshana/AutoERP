<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Core\Tenancy\TenantFeature;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\IncidentReviewAction;
use Modules\VehicleRental\Enums\RunningChartAction;
use Modules\VehicleRental\Enums\VehicleUseAction;
use Modules\VehicleRental\Http\Controllers\AgreementController;
use Modules\VehicleRental\Http\Controllers\BaseRentBillingController;
use Modules\VehicleRental\Http\Controllers\DepositReceiptController;
use Modules\VehicleRental\Http\Controllers\DriverDirectoryController;
use Modules\VehicleRental\Http\Controllers\RentalDashboardController;
use Modules\VehicleRental\Http\Controllers\RentalExpenseController;
use Modules\VehicleRental\Http\Controllers\RentalIncidentAttachmentController;
use Modules\VehicleRental\Http\Controllers\RentalIncidentController;
use Modules\VehicleRental\Http\Controllers\RentalReservationController;
use Modules\VehicleRental\Http\Controllers\RunningChartController;
use Modules\VehicleRental\Http\Controllers\RunningChartReportController;
use Modules\VehicleRental\Http\Controllers\UsageChargeBillingController;
use Modules\VehicleRental\Http\Controllers\VehicleFinanceController;
use Modules\VehicleRental\Http\Controllers\VehicleUseController;

Route::prefix('api/v1/vehicle-rental')->middleware([
    'api', 'auth:'.config('module-auth.protected_route_guard', 'auth-api'),
    config('core.current_user.middleware_alias', 'current.user'), config('core.current_tenant.middleware_alias', 'current.tenant'),
    config('core.current_organization_unit.middleware_alias', 'current.organization-unit').':required', 'tenant.feature:'.TenantFeature::VEHICLE_RENTAL,
])->group(function (): void {
    Route::get('dashboard', [RentalDashboardController::class, 'show']);
    Route::get('driver-employees', [DriverDirectoryController::class, 'index']);

    Route::prefix('reservations')->group(function (): void {
        Route::get('/', [RentalReservationController::class, 'index']);
        Route::post('/', [RentalReservationController::class, 'store']);
        Route::get('{reservation}', [RentalReservationController::class, 'show'])->whereNumber('reservation');
        Route::put('{reservation}', [RentalReservationController::class, 'update'])->whereNumber('reservation');
        Route::patch('{reservation}/transition', [RentalReservationController::class, 'transition'])->whereNumber('reservation');
        Route::post('{reservation}/convert', [RentalReservationController::class, 'convert'])->whereNumber('reservation');
    });

    Route::prefix('finance-agreements')->group(function (): void {
        Route::get('/', [VehicleFinanceController::class, 'index']);
        Route::post('/', [VehicleFinanceController::class, 'store']);
        Route::get('{agreement}', [VehicleFinanceController::class, 'show'])->whereNumber('agreement');
        Route::patch('{agreement}/activate', [VehicleFinanceController::class, 'activate'])->whereNumber('agreement');
        Route::patch('{agreement}/cancel', [VehicleFinanceController::class, 'cancel'])->whereNumber('agreement');
        Route::post('installments/{installment}/payable', [VehicleFinanceController::class, 'createPayable'])->whereNumber('installment');
        Route::patch('installments/refresh-due-statuses', [VehicleFinanceController::class, 'refreshDueStatuses']);
    });

    Route::prefix('expenses')->group(function (): void {
        Route::get('/', [RentalExpenseController::class, 'index']);
        Route::post('/', [RentalExpenseController::class, 'store']);
        Route::get('{expense}', [RentalExpenseController::class, 'show'])->whereNumber('expense');
        Route::patch('{expense}/transition', [RentalExpenseController::class, 'transition'])->whereNumber('expense');
    });

    Route::prefix('incidents')->group(function (): void {
        Route::post('{incident}/attachments', [RentalIncidentAttachmentController::class, 'store'])->whereNumber('incident');
        Route::get('{incident}/attachments/{attachment}', [RentalIncidentAttachmentController::class, 'download'])->whereNumber(['incident', 'attachment']);
        Route::get('vehicle-use-options', [RentalIncidentController::class, 'vehicleUseOptions']);
        Route::get('vehicle-uses/{use}/running-chart-options', [RentalIncidentController::class, 'runningChartOptions'])->whereNumber('use');
        Route::get('/', [RentalIncidentController::class, 'index']);
        Route::post('/', [RentalIncidentController::class, 'store']);
        Route::get('{incident}', [RentalIncidentController::class, 'show'])->whereNumber('incident');
        Route::get('{incident}/history', [RentalIncidentController::class, 'history'])->whereNumber('incident');
        Route::post('{incident}/{action}', [RentalIncidentController::class, 'review'])
            ->whereNumber('incident')
            ->whereIn('action', array_column(IncidentReviewAction::cases(), 'value'));
    });

    Route::prefix('{kind}/agreements')->whereIn('kind', array_column(AgreementKind::cases(), 'value'))->group(function (): void {
        Route::get('/', [AgreementController::class, 'index']);
        Route::post('/', [AgreementController::class, 'store']);
        Route::get('{agreement}', [AgreementController::class, 'show'])->whereNumber('agreement');
        Route::put('{agreement}', [AgreementController::class, 'update'])->whereNumber('agreement');
        Route::post('{agreement}/successor', [AgreementController::class, 'successor'])->whereNumber('agreement');
        Route::get('{agreement}/history', [AgreementController::class, 'history'])->whereNumber('agreement');
        Route::middleware('tenant.feature:'.TenantFeature::INVOICE)->group(function (): void {
            Route::get('{agreement}/base-charges', [BaseRentBillingController::class, 'index'])->whereNumber('agreement');
            Route::post('{agreement}/base-charges', [BaseRentBillingController::class, 'store'])->whereNumber('agreement');
            Route::post('{agreement}/base-charges/{charge}/void', [BaseRentBillingController::class, 'void'])->whereNumber('agreement')->whereNumber('charge');
            Route::post('{agreement}/base-charges/{charge}/reissue', [BaseRentBillingController::class, 'reissue'])->whereNumber('agreement')->whereNumber('charge');
        });
        Route::post('{agreement}/base-rent-preview', [AgreementController::class, 'previewBaseRent'])->whereNumber('agreement');
        Route::post('{agreement}/{action}', [AgreementController::class, 'transition'])->whereNumber('agreement')->whereIn('action', [AgreementAction::Activate->value, AgreementAction::Close->value]);
    });

    Route::prefix('customer/agreements/{agreement}/deposits')->whereNumber('agreement')->middleware('tenant.feature:'.TenantFeature::PAYMENT)->group(function (): void {
        Route::get('/', [DepositReceiptController::class, 'index']);
        Route::post('/', [DepositReceiptController::class, 'store']);
    });

    Route::get('customer/agreements/{agreement}/vehicles', [VehicleUseController::class, 'index'])->whereNumber('agreement');
    Route::post('customer/agreements/{agreement}/vehicles', [VehicleUseController::class, 'store'])->whereNumber('agreement');
    Route::get('vehicles/{vehicle}/sources', [VehicleUseController::class, 'sources'])->whereNumber('vehicle');
    Route::post('vehicle-uses/{use}/replace', [VehicleUseController::class, 'replace'])->whereNumber('use');
    Route::get('vehicle-uses/{use}/history', [VehicleUseController::class, 'history'])->whereNumber('use');
    Route::post('vehicle-uses/{use}/{action}', [VehicleUseController::class, 'transition'])->whereNumber('use')->whereIn('action', [VehicleUseAction::Handover->value, VehicleUseAction::ReturnVehicle->value, VehicleUseAction::Cancel->value]);
    Route::get('vehicle-uses/{use}/running-charts', [RunningChartController::class, 'index'])->whereNumber('use');
    Route::post('vehicle-uses/{use}/running-charts', [RunningChartController::class, 'store'])->whereNumber('use');
    Route::get('vehicle-uses', [VehicleUseController::class, 'register']);

    Route::prefix('running-charts/{chart}/{kind}/charges')->whereNumber('chart')->whereIn('kind', array_column(AgreementKind::cases(), 'value'))->middleware('tenant.feature:'.TenantFeature::INVOICE)->group(function (): void {
        Route::get('/', [UsageChargeBillingController::class, 'index']);
        Route::get('mileage', [UsageChargeBillingController::class, 'previewMileage']);
        Route::post('mileage', [UsageChargeBillingController::class, 'assessMileage']);
        Route::post('/', [UsageChargeBillingController::class, 'store']);
        Route::post('{charge}/reissue', [UsageChargeBillingController::class, 'reissue'])->whereNumber('charge');
        Route::post('{charge}/void', [UsageChargeBillingController::class, 'void'])->whereNumber('charge');
    });

    Route::get('running-charts', [RunningChartController::class, 'register']);
    Route::put('running-charts/{chart}', [RunningChartController::class, 'update'])->whereNumber('chart');
    Route::get('running-charts/{chart}/history', [RunningChartController::class, 'history'])->whereNumber('chart');
    Route::post('running-charts/{chart}/{action}', [RunningChartController::class, 'transition'])->whereNumber('chart')->whereIn('action', [RunningChartAction::Finalize->value, RunningChartAction::Reverse->value]);

    Route::get('vehicles/available', [VehicleUseController::class, 'available']);
    Route::get('reports/running-chart-sheet', [RunningChartReportController::class, 'show']);
    Route::get('reports/running-chart-sheet/export/{format}', [RunningChartReportController::class, 'export'])->whereIn('format', ['pdf', 'print', 'html']);
});
