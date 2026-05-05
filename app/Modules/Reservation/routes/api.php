<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Reservation\Infrastructure\Http\Controllers\ReservationController;

Route::middleware(['auth.configured', 'resolve.tenant'])->group(function (): void {
    Route::get('reservations', [ReservationController::class, 'index']);
    Route::post('reservations', [ReservationController::class, 'store']);
    Route::get('reservations/{id}', [ReservationController::class, 'show']);
    Route::patch('reservations/{id}/status', [ReservationController::class, 'changeStatus']);
    Route::delete('reservations/{id}', [ReservationController::class, 'destroy']);
    Route::get('vehicles/{vehicleId}/reservations', [ReservationController::class, 'byVehicle']);
});
