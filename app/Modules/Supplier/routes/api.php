<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Supplier\Presentation\Http\Controllers\SupplierLifecycleController;
use Modules\Supplier\Presentation\Http\Controllers\SupplierResourceController;

Route::prefix('api/supplier')
    ->middleware('api')
    ->name('supplier.')
    ->group(function (): void {
        Route::prefix('tenants/{tenant}')
            ->name('tenants.')
            ->group(function (): void {
                Route::post('contacts/{contact}/primary', [SupplierLifecycleController::class, 'primaryContact'])->name('contacts.primary');
                Route::post('addresses/{address}/default', [SupplierLifecycleController::class, 'defaultAddress'])->name('addresses.default');
                Route::post('vehicles/{vehicle}/current', [SupplierLifecycleController::class, 'currentVehicle'])->name('vehicles.current');
                Route::post('items/{item}/preferred', [SupplierLifecycleController::class, 'preferredItem'])->name('items.preferred');

                Route::get('{resource}', [SupplierResourceController::class, 'index'])->name('resources.index');
                Route::post('{resource}', [SupplierResourceController::class, 'store'])->name('resources.store');
                Route::get('{resource}/{id}', [SupplierResourceController::class, 'show'])->name('resources.show');
                Route::put('{resource}/{id}', [SupplierResourceController::class, 'update'])->name('resources.update');
                Route::patch('{resource}/{id}', [SupplierResourceController::class, 'update'])->name('resources.patch');
                Route::delete('{resource}/{id}', [SupplierResourceController::class, 'destroy'])->name('resources.destroy');
            });
    });
