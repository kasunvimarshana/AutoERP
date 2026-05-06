<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Rental\Infrastructure\Http\Controllers\RentalAvailabilityBridgeController;
use Modules\Rental\Infrastructure\Http\Controllers\RentalBookingController;
use Modules\Rental\Infrastructure\Http\Controllers\RentalDepositController;
use Modules\Rental\Infrastructure\Http\Controllers\RentalDriverAssignmentController;
use Modules\Rental\Infrastructure\Http\Controllers\RentalIncidentController;

Route::prefix('rentals')
    ->middleware(['auth.configured', 'resolve.tenant'])
    ->group(static function (): void {
        // Availability bridge
        Route::post('availability/reserve', [RentalAvailabilityBridgeController::class, 'reserve'])
            ->name('rentals.availability.reserve');

        Route::post('availability/activate', [RentalAvailabilityBridgeController::class, 'activate'])
            ->name('rentals.availability.activate');

        Route::post('availability/release', [RentalAvailabilityBridgeController::class, 'release'])
            ->name('rentals.availability.release');

        // Bookings CRUD
        Route::get('bookings', [RentalBookingController::class, 'index'])
            ->name('rentals.bookings.index');

        Route::post('bookings', [RentalBookingController::class, 'store'])
            ->name('rentals.bookings.store');

        Route::get('bookings/{id}', [RentalBookingController::class, 'show'])
            ->name('rentals.bookings.show');

        Route::put('bookings/{id}', [RentalBookingController::class, 'update'])
            ->name('rentals.bookings.update');

        Route::delete('bookings/{id}', [RentalBookingController::class, 'destroy'])
            ->name('rentals.bookings.destroy');

        // Booking workflow actions
        Route::post('bookings/{id}/activate', [RentalBookingController::class, 'activate'])
            ->name('rentals.bookings.activate');

        Route::post('bookings/{id}/complete', [RentalBookingController::class, 'complete'])
            ->name('rentals.bookings.complete');

        Route::post('bookings/{id}/cancel', [RentalBookingController::class, 'cancel'])
            ->name('rentals.bookings.cancel');

        // Driver Assignments
        Route::get('bookings/{bookingId}/driver-assignments', [RentalDriverAssignmentController::class, 'index'])
            ->name('rentals.driver-assignments.index');

        Route::post('bookings/{bookingId}/driver-assignments', [RentalDriverAssignmentController::class, 'store'])
            ->name('rentals.driver-assignments.store');

        Route::get('bookings/{bookingId}/driver-assignments/{id}', [RentalDriverAssignmentController::class, 'show'])
            ->name('rentals.driver-assignments.show');

        Route::post('bookings/{bookingId}/driver-assignments/{id}/substitute', [RentalDriverAssignmentController::class, 'substitute'])
            ->name('rentals.driver-assignments.substitute');

        Route::delete('bookings/{bookingId}/driver-assignments/{id}', [RentalDriverAssignmentController::class, 'destroy'])
            ->name('rentals.driver-assignments.destroy');

        // Incidents
        Route::get('incidents', [RentalIncidentController::class, 'index'])
            ->name('rentals.incidents.index');

        Route::post('incidents', [RentalIncidentController::class, 'store'])
            ->name('rentals.incidents.store');

        Route::get('incidents/{id}', [RentalIncidentController::class, 'show'])
            ->name('rentals.incidents.show');

        Route::put('incidents/{id}', [RentalIncidentController::class, 'update'])
            ->name('rentals.incidents.update');

        Route::delete('incidents/{id}', [RentalIncidentController::class, 'destroy'])
            ->name('rentals.incidents.destroy');

        // Deposits
        Route::get('bookings/{bookingId}/deposits', [RentalDepositController::class, 'index'])
            ->name('rentals.deposits.index');

        Route::post('bookings/{bookingId}/deposits', [RentalDepositController::class, 'store'])
            ->name('rentals.deposits.store');

        Route::get('bookings/{bookingId}/deposits/{id}', [RentalDepositController::class, 'show'])
            ->name('rentals.deposits.show');

        Route::post('bookings/{bookingId}/deposits/{id}/release', [RentalDepositController::class, 'release'])
            ->name('rentals.deposits.release');

        Route::delete('bookings/{bookingId}/deposits/{id}', [RentalDepositController::class, 'destroy'])
            ->name('rentals.deposits.destroy');
    });
