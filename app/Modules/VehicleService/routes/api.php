<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\VehicleService\Presentation\Http\Controllers\VehicleServiceController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit');

Route::prefix('api/vehicle-service')
    ->middleware(['api', 'auth:'.$protectedGuard, $currentUserMiddleware, $currentTenantMiddleware, $currentOrganizationUnitMiddleware])
    ->name('vehicle-service.')
    ->group(function (): void {
        Route::get('dashboard', [VehicleServiceController::class, 'dashboard'])->name('dashboard');
        Route::get('lookups/{type}', [VehicleServiceController::class, 'lookup'])->name('lookups');

        Route::get('service-types', [VehicleServiceController::class, 'serviceTypes'])->name('service-types.index');
        Route::post('service-types', [VehicleServiceController::class, 'storeServiceType'])->name('service-types.store');
        Route::get('service-types/{serviceType}', [VehicleServiceController::class, 'showServiceType'])->name('service-types.show');
        Route::match(['put', 'patch'], 'service-types/{serviceType}', [VehicleServiceController::class, 'updateServiceType'])->name('service-types.update');
        Route::delete('service-types/{serviceType}', [VehicleServiceController::class, 'destroyServiceType'])->name('service-types.destroy');

        Route::get('job-cards', [VehicleServiceController::class, 'jobs'])->name('job-cards.index');
        Route::post('job-cards', [VehicleServiceController::class, 'storeJob'])->name('job-cards.store');
        Route::get('job-cards/{jobCard}', [VehicleServiceController::class, 'showJob'])->name('job-cards.show');
        Route::match(['put', 'patch'], 'job-cards/{jobCard}', [VehicleServiceController::class, 'updateJob'])->name('job-cards.update');
        Route::delete('job-cards/{jobCard}', [VehicleServiceController::class, 'destroyJob'])->name('job-cards.destroy');
        Route::post('job-cards/{jobCard}/start', [VehicleServiceController::class, 'startJob'])->name('job-cards.start');
        Route::post('job-cards/{jobCard}/consume-inventory', [VehicleServiceController::class, 'consumeInventory'])->name('job-cards.consume-inventory');
        Route::post('job-cards/{jobCard}/complete', [VehicleServiceController::class, 'completeJob'])->name('job-cards.complete');
        Route::post('job-cards/{jobCard}/cancel', [VehicleServiceController::class, 'cancelJob'])->name('job-cards.cancel');
        Route::post('job-cards/{jobCard}/invoice', [VehicleServiceController::class, 'invoiceJob'])->name('job-cards.invoice');
    });
