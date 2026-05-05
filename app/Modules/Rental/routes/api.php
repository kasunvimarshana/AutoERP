<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Rental\Infrastructure\Http\Controllers\RentalChargeController;
use Modules\Rental\Infrastructure\Http\Controllers\RentalController;

Route::middleware(['auth.configured', 'resolve.tenant'])->group(function (): void {
    // Rentals
    Route::get('/rentals', [RentalController::class, 'index']);
    Route::post('/rentals', [RentalController::class, 'store']);
    Route::get('/rentals/{id}', [RentalController::class, 'show']);
    Route::put('/rentals/{id}', [RentalController::class, 'update']);
    Route::delete('/rentals/{id}', [RentalController::class, 'destroy']);

    // Rental status transitions
    Route::patch('/rentals/{id}/confirm', [RentalController::class, 'confirm']);
    Route::patch('/rentals/{id}/start', [RentalController::class, 'start']);
    Route::patch('/rentals/{id}/complete', [RentalController::class, 'complete']);
    Route::patch('/rentals/{id}/cancel', [RentalController::class, 'cancel']);

    // Rental charges (nested)
    Route::get('/rentals/{rentalId}/charges', [RentalChargeController::class, 'index']);
    Route::post('/rentals/{rentalId}/charges', [RentalChargeController::class, 'store']);
    Route::get('/rentals/{rentalId}/charges/{id}', [RentalChargeController::class, 'show']);
    Route::delete('/rentals/{rentalId}/charges/{id}', [RentalChargeController::class, 'destroy']);
});
