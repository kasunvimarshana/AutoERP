<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Driver\Infrastructure\Http\Controllers\AvailabilityController;
use Modules\Driver\Infrastructure\Http\Controllers\CommissionController;
use Modules\Driver\Infrastructure\Http\Controllers\DriverController;
use Modules\Driver\Infrastructure\Http\Controllers\LicenseController;

$registerRoutes = static function (): void {
        // Driver routes
        Route::post('/', [DriverController::class, 'create']);
        Route::get('/', [DriverController::class, 'index']);
        Route::get('/available', [DriverController::class, 'available']);
        Route::get('/{id}', [DriverController::class, 'show']);
        Route::put('/{id}', [DriverController::class, 'update']);
        Route::delete('/{id}', [DriverController::class, 'delete']);

        // License routes
        Route::post('/{driverId}/licenses', [LicenseController::class, 'create']);
        Route::get('/{driverId}/licenses', [LicenseController::class, 'getByDriver']);
        Route::get('/licenses/expiring', [LicenseController::class, 'expiring']);
        Route::put('/licenses/{licenseId}', [LicenseController::class, 'update']);
        Route::delete('/licenses/{licenseId}', [LicenseController::class, 'delete']);

        // Availability routes
        Route::post('/{driverId}/availability', [AvailabilityController::class, 'create']);
        Route::get('/{driverId}/availability', [AvailabilityController::class, 'getByDriver']);
        Route::put('/availability/{id}', [AvailabilityController::class, 'update']);

        // Commission routes
        Route::get('/{driverId}/commissions', [CommissionController::class, 'getByDriver']);
        Route::get('/commissions/pending', [CommissionController::class, 'getPending']);
};

Route::middleware(['api', 'auth.configured', 'resolve.tenant'])
    ->prefix('api/v1/drivers')
    ->group($registerRoutes);

Route::middleware(['api', 'auth.configured', 'resolve.tenant'])
    ->prefix('api/drivers')
    ->group($registerRoutes);
