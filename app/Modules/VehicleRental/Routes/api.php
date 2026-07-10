<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\VehicleRental\Http\Controllers\RentalAgreementController;
use Modules\VehicleRental\Http\Controllers\RentalAllocationController;
use Modules\VehicleRental\Http\Controllers\RentalAvailabilityController;
use Modules\VehicleRental\Http\Controllers\RentalCalculationController;
use Modules\VehicleRental\Http\Controllers\RentalContextController;
use Modules\VehicleRental\Http\Controllers\RentalCustodyController;
use Modules\VehicleRental\Http\Controllers\RentalDepositController;
use Modules\VehicleRental\Http\Controllers\RentalExpenseController;
use Modules\VehicleRental\Http\Controllers\RentalRateVersionController;
use Modules\VehicleRental\Http\Controllers\RentalReplacementController;
use Modules\VehicleRental\Http\Controllers\RentalReservationController;
use Modules\VehicleRental\Http\Controllers\RentalUsageController;
use Modules\VehicleRental\Http\Controllers\RentalUsageFactController;
use Modules\VehicleRental\Http\Controllers\VehicleFinanceController;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit').':required',
    'tenant.feature:vehicle-rental',
];

Route::prefix('api/v1/vehicle-rental')->middleware($middleware)->name('api.v1.vehicle-rental.')->group(function (): void {
    Route::get('metadata', [RentalContextController::class, 'metadata'])->name('metadata');
    Route::get('dashboard', [RentalContextController::class, 'dashboard'])->name('dashboard');
    Route::get('vehicles/available', [RentalAvailabilityController::class, 'index'])->name('vehicles.available');

    Route::get('reservations', [RentalReservationController::class, 'index'])->name('reservations.index');
    Route::post('reservations', [RentalReservationController::class, 'store'])->name('reservations.store');
    Route::get('reservations/{reservation}', [RentalReservationController::class, 'show'])->whereNumber('reservation')->name('reservations.show');
    Route::put('reservations/{reservation}', [RentalReservationController::class, 'update'])->whereNumber('reservation')->name('reservations.update');
    Route::patch('reservations/{reservation}/transition', [RentalReservationController::class, 'transition'])->whereNumber('reservation')->name('reservations.transition');

    Route::get('agreements', [RentalAgreementController::class, 'index'])->name('agreements.index');
    Route::post('agreements', [RentalAgreementController::class, 'store'])->name('agreements.store');
    Route::get('agreements/{agreement}', [RentalAgreementController::class, 'show'])->whereNumber('agreement')->name('agreements.show');
    Route::put('agreements/{agreement}', [RentalAgreementController::class, 'update'])->whereNumber('agreement')->name('agreements.update');
    Route::delete('agreements/{agreement}', [RentalAgreementController::class, 'destroy'])->whereNumber('agreement')->name('agreements.destroy');
    Route::patch('agreements/{agreement}/transition', [RentalAgreementController::class, 'transition'])->whereNumber('agreement')->name('agreements.transition');
    Route::post('agreements/{agreement}/rate-versions', [RentalRateVersionController::class, 'store'])->whereNumber('agreement')->name('rate-versions.store');
    Route::patch('rate-versions/{version}/activate', [RentalRateVersionController::class, 'activate'])->whereNumber('version')->name('rate-versions.activate');

    Route::get('allocations', [RentalAllocationController::class, 'index'])->name('allocations.index');
    Route::post('agreements/{agreement}/allocations', [RentalAllocationController::class, 'store'])->whereNumber('agreement')->name('allocations.store');
    Route::get('allocations/{allocation}', [RentalAllocationController::class, 'show'])->whereNumber('allocation')->name('allocations.show');
    Route::post('allocations/{allocation}/drivers', [RentalAllocationController::class, 'assignDriver'])->whereNumber('allocation')->name('allocations.drivers.store');
    Route::patch('allocations/{allocation}/cancel', [RentalAllocationController::class, 'cancel'])->whereNumber('allocation')->name('allocations.cancel');

    Route::get('custody-events', [RentalCustodyController::class, 'index'])->name('custody.index');
    Route::post('allocations/{allocation}/custody-events', [RentalCustodyController::class, 'store'])->whereNumber('allocation')->name('custody.store');
    Route::get('custody-events/{event}', [RentalCustodyController::class, 'show'])->whereNumber('event')->name('custody.show');
    Route::patch('custody-events/{event}/confirm', [RentalCustodyController::class, 'confirm'])->whereNumber('event')->name('custody.confirm');
    Route::patch('custody-events/{event}/reverse', [RentalCustodyController::class, 'reverse'])->whereNumber('event')->name('custody.reverse');

    Route::post('allocations/{allocation}/replacement', [RentalReplacementController::class, 'store'])->whereNumber('allocation')->name('replacements.store');
    Route::get('replacements/{replacement}', [RentalReplacementController::class, 'show'])->whereNumber('replacement')->name('replacements.show');

    Route::get('usage-logs', [RentalUsageController::class, 'index'])->name('usage.index');
    Route::post('allocations/{allocation}/usage-logs', [RentalUsageController::class, 'store'])->whereNumber('allocation')->name('usage.store');
    Route::get('usage-logs/{usage}', [RentalUsageController::class, 'show'])->whereNumber('usage')->name('usage.show');
    Route::patch('usage-logs/{usage}/transition', [RentalUsageController::class, 'transition'])->whereNumber('usage')->name('usage.transition');
    Route::get('usage-facts/{fact}', [RentalUsageFactController::class, 'show'])->whereNumber('fact')->name('usage-facts.show');
    Route::patch('usage-facts/{fact}', [RentalUsageFactController::class, 'update'])->whereNumber('fact')->name('usage-facts.update');
    Route::patch('usage-facts/{fact}/transition', [RentalUsageFactController::class, 'transition'])->whereNumber('fact')->name('usage-facts.transition');

    Route::get('expenses', [RentalExpenseController::class, 'index'])->name('expenses.index');
    Route::post('expenses', [RentalExpenseController::class, 'store'])->name('expenses.store');
    Route::get('expenses/{expense}', [RentalExpenseController::class, 'show'])->whereNumber('expense')->name('expenses.show');
    Route::patch('expenses/{expense}/transition', [RentalExpenseController::class, 'transition'])->whereNumber('expense')->name('expenses.transition');

    Route::get('calculation-runs', [RentalCalculationController::class, 'index'])->name('calculations.index');
    Route::post('agreements/{agreement}/calculate', [RentalCalculationController::class, 'calculate'])->whereNumber('agreement')->name('calculations.calculate');
    Route::get('calculation-runs/{run}', [RentalCalculationController::class, 'show'])->whereNumber('run')->name('calculations.show');
    Route::patch('calculation-runs/{run}/transition', [RentalCalculationController::class, 'transition'])->whereNumber('run')->name('calculations.transition');
    Route::post('calculation-runs/{run}/invoice', [RentalCalculationController::class, 'createInvoice'])->whereNumber('run')->name('calculations.invoice');

    Route::get('deposits', [RentalDepositController::class, 'index'])->name('deposits.index');
    Route::get('deposits/{deposit}', [RentalDepositController::class, 'show'])->whereNumber('deposit')->name('deposits.show');
    Route::post('deposits/{deposit}/receive', [RentalDepositController::class, 'receive'])->whereNumber('deposit')->name('deposits.receive');
    Route::post('deposits/{deposit}/apply', [RentalDepositController::class, 'apply'])->whereNumber('deposit')->name('deposits.apply');
    Route::post('deposits/{deposit}/refund', [RentalDepositController::class, 'refund'])->whereNumber('deposit')->name('deposits.refund');
    Route::post('deposits/{deposit}/forfeit', [RentalDepositController::class, 'forfeit'])->whereNumber('deposit')->name('deposits.forfeit');
    Route::patch('deposit-links/{link}/reverse', [RentalDepositController::class, 'reverse'])->whereNumber('link')->name('deposits.links.reverse');

    Route::get('finance-agreements', [VehicleFinanceController::class, 'index'])->name('finance-agreements.index');
    Route::post('finance-agreements', [VehicleFinanceController::class, 'store'])->name('finance-agreements.store');
    Route::get('finance-agreements/{agreement}', [VehicleFinanceController::class, 'show'])->whereNumber('agreement')->name('finance-agreements.show');
    Route::patch('finance-agreements/{agreement}/activate', [VehicleFinanceController::class, 'activate'])->whereNumber('agreement')->name('finance-agreements.activate');
    Route::post('finance-installments/{installment}/payable', [VehicleFinanceController::class, 'createPayable'])->whereNumber('installment')->name('finance-installments.payable');
    Route::patch('finance-installments/refresh-due-statuses', [VehicleFinanceController::class, 'refreshDueStatuses'])->name('finance-installments.refresh-due-statuses');
});
