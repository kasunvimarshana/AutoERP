<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Customer\Presentation\Http\Controllers\CustomerLifecycleController;
use Modules\Customer\Presentation\Http\Controllers\CustomerResourceController;

Route::prefix('api/customer')
    ->middleware('api')
    ->name('customer.')
    ->group(function (): void {
        Route::prefix('tenants/{tenant}')
            ->name('tenants.')
            ->group(function (): void {
                Route::post('contacts/{contact}/primary', [CustomerLifecycleController::class, 'primaryContact'])->name('contacts.primary');
                Route::post('addresses/{address}/default', [CustomerLifecycleController::class, 'defaultAddress'])->name('addresses.default');
                Route::post('vehicles/{vehicle}/current', [CustomerLifecycleController::class, 'currentVehicle'])->name('vehicles.current');

                Route::get('{resource}', [CustomerResourceController::class, 'index'])->name('resources.index');
                Route::post('{resource}', [CustomerResourceController::class, 'store'])->name('resources.store');
                Route::get('{resource}/{id}', [CustomerResourceController::class, 'show'])->name('resources.show');
                Route::put('{resource}/{id}', [CustomerResourceController::class, 'update'])->name('resources.update');
                Route::patch('{resource}/{id}', [CustomerResourceController::class, 'update'])->name('resources.patch');
                Route::delete('{resource}/{id}', [CustomerResourceController::class, 'destroy'])->name('resources.destroy');
            });
    });
