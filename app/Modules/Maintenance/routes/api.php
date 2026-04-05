<?php
declare(strict_types=1);
use Illuminate\Support\Facades\Route;
Route::prefix('api')->group(function () {
    Route::prefix('maintenance')->group(function () {
        Route::get('/orders',                  [\Modules\Maintenance\Infrastructure\Http\Controllers\ServiceOrderController::class, 'index']);
        Route::post('/orders',                 [\Modules\Maintenance\Infrastructure\Http\Controllers\ServiceOrderController::class, 'store']);
        Route::get('/orders/{id}',             [\Modules\Maintenance\Infrastructure\Http\Controllers\ServiceOrderController::class, 'show']);
        Route::put('/orders/{id}',             [\Modules\Maintenance\Infrastructure\Http\Controllers\ServiceOrderController::class, 'update']);
        Route::delete('/orders/{id}',          [\Modules\Maintenance\Infrastructure\Http\Controllers\ServiceOrderController::class, 'destroy']);
        Route::post('/orders/{id}/start',      [\Modules\Maintenance\Infrastructure\Http\Controllers\ServiceOrderController::class, 'start']);
        Route::post('/orders/{id}/complete',   [\Modules\Maintenance\Infrastructure\Http\Controllers\ServiceOrderController::class, 'complete']);
        Route::post('/orders/{id}/cancel',     [\Modules\Maintenance\Infrastructure\Http\Controllers\ServiceOrderController::class, 'cancel']);
        Route::get('/schedules',               [\Modules\Maintenance\Infrastructure\Http\Controllers\MaintenanceScheduleController::class, 'index']);
        Route::post('/schedules',              [\Modules\Maintenance\Infrastructure\Http\Controllers\MaintenanceScheduleController::class, 'store']);
        Route::get('/schedules/{id}',          [\Modules\Maintenance\Infrastructure\Http\Controllers\MaintenanceScheduleController::class, 'show']);
        Route::put('/schedules/{id}',          [\Modules\Maintenance\Infrastructure\Http\Controllers\MaintenanceScheduleController::class, 'update']);
        Route::delete('/schedules/{id}',       [\Modules\Maintenance\Infrastructure\Http\Controllers\MaintenanceScheduleController::class, 'destroy']);
        Route::post('/schedules/process-due',  [\Modules\Maintenance\Infrastructure\Http\Controllers\MaintenanceScheduleController::class, 'processDue']);
    });
});
