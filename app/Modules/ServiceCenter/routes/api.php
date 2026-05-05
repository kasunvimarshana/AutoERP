<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\ServiceCenter\Infrastructure\Http\Controllers\ServiceJobController;

Route::middleware(['auth.configured', 'resolve.tenant'])->group(function (): void {
    Route::get('service-jobs', [ServiceJobController::class, 'index']);
    Route::post('service-jobs', [ServiceJobController::class, 'store']);
    Route::get('service-jobs/{id}', [ServiceJobController::class, 'show']);
    Route::put('service-jobs/{id}', [ServiceJobController::class, 'update']);
    Route::delete('service-jobs/{id}', [ServiceJobController::class, 'destroy']);
    Route::patch('service-jobs/{id}/status', [ServiceJobController::class, 'changeStatus']);

    Route::get('vehicles/{vehicleId}/service-jobs', [ServiceJobController::class, 'byVehicle']);
});
