<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Service\Infrastructure\Http\Controllers\ServiceJobCardController;
use Modules\Service\Infrastructure\Http\Controllers\ServiceMaintenancePlanController;

Route::prefix('service')
    ->middleware(['auth.configured', 'resolve.tenant'])
    ->group(function (): void {

        // Maintenance plans
        Route::get('maintenance-plans', [ServiceMaintenancePlanController::class, 'index'])
            ->name('service.maintenance-plans.index');
        Route::post('maintenance-plans', [ServiceMaintenancePlanController::class, 'store'])
            ->name('service.maintenance-plans.store');
        Route::get('maintenance-plans/{plan}', [ServiceMaintenancePlanController::class, 'show'])
            ->name('service.maintenance-plans.show');
        Route::put('maintenance-plans/{plan}', [ServiceMaintenancePlanController::class, 'update'])
            ->name('service.maintenance-plans.update');
        Route::delete('maintenance-plans/{plan}', [ServiceMaintenancePlanController::class, 'destroy'])
            ->name('service.maintenance-plans.destroy');

        // Job cards
        Route::get('job-cards', [ServiceJobCardController::class, 'index'])
            ->name('service.job-cards.index');
        Route::post('job-cards', [ServiceJobCardController::class, 'store'])
            ->name('service.job-cards.store');
        Route::get('job-cards/{jobCard}', [ServiceJobCardController::class, 'show'])
            ->name('service.job-cards.show');
        Route::put('job-cards/{jobCard}/status', [ServiceJobCardController::class, 'updateStatus'])
            ->name('service.job-cards.update-status');
        Route::post('job-cards/{jobCard}/complete', [ServiceJobCardController::class, 'complete'])
            ->name('service.job-cards.complete');
        Route::delete('job-cards/{jobCard}', [ServiceJobCardController::class, 'destroy'])
            ->name('service.job-cards.destroy');
    });
