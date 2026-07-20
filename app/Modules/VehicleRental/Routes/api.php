<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\VehicleRental\Constants\VehicleRentalPermission;
use Modules\VehicleRental\Http\Controllers\RentalAgreementController;
use Modules\VehicleRental\Http\Controllers\RentalAssignmentController;
use Modules\VehicleRental\Http\Controllers\RentalCalculationController;
use Modules\VehicleRental\Http\Controllers\RentalLookupController;
use Modules\VehicleRental\Http\Controllers\RentalRunningChartController;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit').':required',
    'tenant.feature:vehicle-rental',
];
$permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');
$requires = static fn (string $permission): string => $permissionMiddleware.':'.$permission;

Route::prefix('api/v1/vehicle-rental')
    ->middleware($middleware)
    ->name('api.v1.vehicle-rental.')
    ->group(function () use ($requires): void {
        Route::middleware($requires(VehicleRentalPermission::AGREEMENTS_VIEW))->group(function (): void {
            Route::get('agreements', [RentalAgreementController::class, 'index'])->name('agreements.index');
            Route::get('agreements/{agreement}', [RentalAgreementController::class, 'show'])
                ->whereNumber('agreement')->name('agreements.show');
        });
        Route::middleware($requires(VehicleRentalPermission::AGREEMENTS_MANAGE))->group(function (): void {
            Route::get('lookups/agreement-form', [RentalLookupController::class, 'agreementForm'])
                ->name('lookups.agreement-form');
            Route::post('agreements', [RentalAgreementController::class, 'store'])->name('agreements.store');
            Route::put('agreements/{agreement}', [RentalAgreementController::class, 'update'])
                ->whereNumber('agreement')->name('agreements.update');
            Route::post('agreements/{agreement}/rate-versions', [RentalAgreementController::class, 'storeRateVersion'])
                ->whereNumber('agreement')->name('agreements.rate-versions.store');
            Route::post('agreements/{agreement}/activate', [RentalAgreementController::class, 'activate'])
                ->whereNumber('agreement')->name('agreements.activate');
            Route::post('agreements/{agreement}/close', [RentalAgreementController::class, 'close'])
                ->whereNumber('agreement')->name('agreements.close');
        });

        Route::middleware($requires(VehicleRentalPermission::ASSIGNMENTS_VIEW))->group(function (): void {
            Route::get('assignments', [RentalAssignmentController::class, 'index'])->name('assignments.index');
            Route::get('assignments/{assignment}', [RentalAssignmentController::class, 'show'])
                ->whereNumber('assignment')->name('assignments.show');
        });
        Route::middleware($requires(VehicleRentalPermission::ASSIGNMENTS_MANAGE))->group(function (): void {
            Route::post('assignments', [RentalAssignmentController::class, 'store'])->name('assignments.store');
            Route::post('assignments/{assignment}/replace', [RentalAssignmentController::class, 'replace'])
                ->whereNumber('assignment')->name('assignments.replace');
            Route::post('assignments/{assignment}/custody', [RentalAssignmentController::class, 'custody'])
                ->whereNumber('assignment')->name('assignments.custody');
            Route::post('assignments/{assignment}/cancel', [RentalAssignmentController::class, 'cancel'])
                ->whereNumber('assignment')->name('assignments.cancel');
        });

        Route::middleware($requires(VehicleRentalPermission::RUNNING_CHARTS_VIEW))->group(function (): void {
            Route::get('running-charts', [RentalRunningChartController::class, 'index'])->name('running-charts.index');
            Route::get('running-charts/{runningChart}', [RentalRunningChartController::class, 'show'])
                ->whereNumber('runningChart')->name('running-charts.show');
        });
        Route::middleware($requires(VehicleRentalPermission::RUNNING_CHARTS_MANAGE))->group(function (): void {
            Route::post('running-charts', [RentalRunningChartController::class, 'store'])->name('running-charts.store');
            Route::put('running-charts/{runningChart}', [RentalRunningChartController::class, 'update'])
                ->whereNumber('runningChart')->name('running-charts.update');
            Route::post('running-charts/{runningChart}/finalize', [RentalRunningChartController::class, 'finalize'])
                ->whereNumber('runningChart')->name('running-charts.finalize');
            Route::post('running-charts/{runningChart}/reverse', [RentalRunningChartController::class, 'reverse'])
                ->whereNumber('runningChart')->name('running-charts.reverse');
        });

        Route::middleware($requires(VehicleRentalPermission::CALCULATIONS_VIEW))->group(function (): void {
            Route::get('calculations', [RentalCalculationController::class, 'index'])->name('calculations.index');
            Route::get('calculations/{calculation}', [RentalCalculationController::class, 'show'])
                ->whereNumber('calculation')->name('calculations.show');
        });
        Route::middleware($requires(VehicleRentalPermission::CALCULATIONS_MANAGE))->group(function (): void {
            Route::post('agreements/{agreement}/calculations', [RentalCalculationController::class, 'calculate'])
                ->whereNumber('agreement')->name('agreements.calculations.store');
            Route::post('calculations/{calculation}/cancel', [RentalCalculationController::class, 'cancel'])
                ->whereNumber('calculation')->name('calculations.cancel');
        });
    });
