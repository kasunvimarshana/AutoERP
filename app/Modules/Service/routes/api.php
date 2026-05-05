<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Service\Infrastructure\Http\Controllers\ServiceAvailabilityBridgeController;
use Modules\Service\Infrastructure\Http\Controllers\ServiceWorkOrderController;

Route::prefix('services')
    ->middleware(['auth.configured', 'resolve.tenant'])
    ->group(static function (): void {
        Route::post('availability/start-downtime', [ServiceAvailabilityBridgeController::class, 'startDowntime'])
            ->name('services.availability.start-downtime');

        Route::post('availability/end-downtime', [ServiceAvailabilityBridgeController::class, 'endDowntime'])
            ->name('services.availability.end-downtime');

        Route::get('work-orders', [ServiceWorkOrderController::class, 'index'])
            ->name('services.work-orders.index');

        Route::post('work-orders', [ServiceWorkOrderController::class, 'store'])
            ->name('services.work-orders.store');

        Route::get('work-orders/{id}', [ServiceWorkOrderController::class, 'show'])
            ->name('services.work-orders.show');

        Route::put('work-orders/{id}', [ServiceWorkOrderController::class, 'update'])
            ->name('services.work-orders.update');

        Route::delete('work-orders/{id}', [ServiceWorkOrderController::class, 'destroy'])
            ->name('services.work-orders.destroy');

        Route::post('work-orders/{id}/complete', [ServiceWorkOrderController::class, 'complete'])
            ->name('services.work-orders.complete');

        Route::post('work-orders/{id}/cancel', [ServiceWorkOrderController::class, 'cancel'])
            ->name('services.work-orders.cancel');
    });
