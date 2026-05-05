<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Rental\Infrastructure\Http\Controllers\RentalAgreementController;
use Modules\Rental\Infrastructure\Http\Controllers\RentalReservationController;
use Modules\Rental\Infrastructure\Http\Controllers\RentalTransactionController;

$registerRoutes = static function (): void {
        Route::post('/reservations', [RentalReservationController::class, 'create']);
        Route::get('/reservations', [RentalReservationController::class, 'index']);
        Route::get('/reservations/{id}', [RentalReservationController::class, 'show']);
        Route::put('/reservations/{id}', [RentalReservationController::class, 'update']);
        Route::post('/reservations/{id}/confirm', [RentalReservationController::class, 'confirm']);
        Route::post('/reservations/{id}/cancel', [RentalReservationController::class, 'cancel']);

        Route::post('/agreements', [RentalAgreementController::class, 'create']);
        Route::get('/agreements/{id}', [RentalAgreementController::class, 'show']);
        Route::get('/agreements/active', [RentalAgreementController::class, 'active']);

        Route::post('/transactions/checkout', [RentalTransactionController::class, 'checkOut']);
        Route::post('/transactions/checkin', [RentalTransactionController::class, 'checkIn']);
        Route::get('/transactions/open', [RentalTransactionController::class, 'open']);
};

Route::middleware(['api', 'auth.configured', 'resolve.tenant'])
    ->prefix('api/v1/rentals')
    ->group($registerRoutes);

Route::middleware(['api', 'auth.configured', 'resolve.tenant'])
    ->prefix('api/rentals')
    ->group($registerRoutes);
