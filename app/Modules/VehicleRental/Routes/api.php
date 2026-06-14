<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\VehicleRental\Http\Controllers\RentalAgreementController;
use Modules\VehicleRental\Http\Controllers\RentalAgreementVehicleController;
use Modules\VehicleRental\Http\Controllers\RentalAvailabilityController;
use Modules\VehicleRental\Http\Controllers\RentalChargeController;
use Modules\VehicleRental\Http\Controllers\RentalExpenseController;
use Modules\VehicleRental\Http\Controllers\RentalInspectionController;
use Modules\VehicleRental\Http\Controllers\RentalInvoiceController;
use Modules\VehicleRental\Http\Controllers\RentalPaymentController;
use Modules\VehicleRental\Http\Controllers\RentalReservationController;
use Modules\VehicleRental\Http\Controllers\RentalUsageController;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit'),
];

Route::prefix('api/v1/vehicle-rental')->middleware($middleware)->name('api.v1.vehicle-rental.')->group(function (): void {
    Route::get('availability', [RentalAvailabilityController::class, 'index'])->name('availability.index');

    Route::get('reservations', [RentalReservationController::class, 'index'])->name('reservations.index');
    Route::post('reservations', [RentalReservationController::class, 'store'])->name('reservations.store');
    Route::get('reservations/{reservation}', [RentalReservationController::class, 'show'])->whereNumber('reservation')->name('reservations.show');
    Route::put('reservations/{reservation}', [RentalReservationController::class, 'update'])->whereNumber('reservation')->name('reservations.update');
    Route::patch('reservations/{reservation}/pending', [RentalReservationController::class, 'pending'])->whereNumber('reservation')->name('reservations.pending');
    Route::patch('reservations/{reservation}/confirm', [RentalReservationController::class, 'confirm'])->whereNumber('reservation')->name('reservations.confirm');
    Route::patch('reservations/{reservation}/cancel', [RentalReservationController::class, 'cancel'])->whereNumber('reservation')->name('reservations.cancel');
    Route::get('reservations/{reservation}/status-history', [RentalReservationController::class, 'history'])->whereNumber('reservation')->name('reservations.history');

    Route::get('agreements', [RentalAgreementController::class, 'index'])->name('agreements.index');
    Route::post('agreements', [RentalAgreementController::class, 'store'])->name('agreements.store');
    Route::get('agreements/{agreement}', [RentalAgreementController::class, 'show'])->whereNumber('agreement')->name('agreements.show');
    Route::patch('agreements/{agreement}/confirm', [RentalAgreementController::class, 'confirm'])->whereNumber('agreement')->name('agreements.confirm');
    Route::patch('agreements/{agreement}/activate', [RentalAgreementController::class, 'activate'])->whereNumber('agreement')->name('agreements.activate');
    Route::patch('agreements/{agreement}/returned', [RentalAgreementController::class, 'markReturned'])->whereNumber('agreement')->name('agreements.returned');
    Route::patch('agreements/{agreement}/complete', [RentalAgreementController::class, 'complete'])->whereNumber('agreement')->name('agreements.complete');
    Route::patch('agreements/{agreement}/cancel', [RentalAgreementController::class, 'cancel'])->whereNumber('agreement')->name('agreements.cancel');
    Route::get('agreements/{agreement}/status-history', [RentalAgreementController::class, 'history'])->whereNumber('agreement')->name('agreements.history');

    Route::get('agreements/{agreement}/vehicles', [RentalAgreementVehicleController::class, 'index'])->whereNumber('agreement')->name('vehicles.index');
    Route::post('agreements/{agreement}/vehicles', [RentalAgreementVehicleController::class, 'store'])->whereNumber('agreement')->name('vehicles.store');
    Route::post('agreements/{agreement}/vehicles/{allocation}/replace', [RentalAgreementVehicleController::class, 'replace'])->whereNumber(['agreement', 'allocation'])->name('vehicles.replace');
    Route::put('agreements/{agreement}/vehicles/{allocation}/pickup', [RentalInspectionController::class, 'pickup'])->whereNumber(['agreement', 'allocation'])->name('inspections.pickup');
    Route::put('agreements/{agreement}/vehicles/{allocation}/return', [RentalInspectionController::class, 'return'])->whereNumber(['agreement', 'allocation'])->name('inspections.return');

    Route::get('agreements/{agreement}/usage-logs', [RentalUsageController::class, 'index'])->whereNumber('agreement')->name('usage.index');
    Route::post('agreements/{agreement}/usage-logs', [RentalUsageController::class, 'store'])->whereNumber('agreement')->name('usage.store');
    Route::post('agreements/{agreement}/usage-logs/{usageLog}/events', [RentalUsageController::class, 'storeEvent'])->whereNumber(['agreement', 'usageLog'])->name('usage.events.store');

    Route::get('agreements/{agreement}/expenses', [RentalExpenseController::class, 'index'])->whereNumber('agreement')->name('expenses.index');
    Route::post('agreements/{agreement}/expenses', [RentalExpenseController::class, 'store'])->whereNumber('agreement')->name('expenses.store');
    Route::patch('agreements/{agreement}/expenses/{expense}/approve', [RentalExpenseController::class, 'approve'])->whereNumber(['agreement', 'expense'])->name('expenses.approve');
    Route::patch('agreements/{agreement}/expenses/{expense}/reject', [RentalExpenseController::class, 'reject'])->whereNumber(['agreement', 'expense'])->name('expenses.reject');

    Route::get('agreements/{agreement}/charges', [RentalChargeController::class, 'index'])->whereNumber('agreement')->name('charges.index');
    Route::post('agreements/{agreement}/charges/generate', [RentalChargeController::class, 'generate'])->whereNumber('agreement')->name('charges.generate');
    Route::patch('agreements/{agreement}/charges/approve', [RentalChargeController::class, 'approveAll'])->whereNumber('agreement')->name('charges.approve');

    Route::get('agreements/{agreement}/invoice-charges', [RentalInvoiceController::class, 'charges'])->whereNumber('agreement')->name('invoices.charges');
    Route::post('agreements/{agreement}/invoices/preview', [RentalInvoiceController::class, 'preview'])->whereNumber('agreement')->name('invoices.preview');
    Route::post('agreements/{agreement}/invoices', [RentalInvoiceController::class, 'store'])->whereNumber('agreement')->name('invoices.store');
    Route::post('agreements/{agreement}/payments/prepare', [RentalPaymentController::class, 'prepare'])->whereNumber('agreement')->name('payments.prepare');
    Route::post('agreements/{agreement}/payments', [RentalPaymentController::class, 'store'])->whereNumber('agreement')->name('payments.store');
});
