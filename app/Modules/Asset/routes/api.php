<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Asset\Infrastructure\Http\Controllers\AssetController;
use Modules\Asset\Infrastructure\Http\Controllers\AssetDepreciationController;
use Modules\Asset\Infrastructure\Http\Controllers\AssetDocumentController;
use Modules\Asset\Infrastructure\Http\Controllers\AssetOwnerController;
use Modules\Asset\Infrastructure\Http\Controllers\VehicleController;

$registerRoutes = static function (): void {
        // Asset Owner routes
        Route::post('/owners', [AssetOwnerController::class, 'create']);
        Route::get('/owners', [AssetOwnerController::class, 'index']);
        Route::get('/owners/{id}', [AssetOwnerController::class, 'show']);
        Route::put('/owners/{id}', [AssetOwnerController::class, 'update']);
        Route::delete('/owners/{id}', [AssetOwnerController::class, 'delete']);

        // Asset routes
        Route::post('/', [AssetController::class, 'create']);
        Route::get('/', [AssetController::class, 'index']);
        Route::get('/{id}', [AssetController::class, 'show']);
        Route::put('/{id}', [AssetController::class, 'update']);
        Route::delete('/{id}', [AssetController::class, 'delete']);

        // Vehicle routes
        Route::post('/vehicles', [VehicleController::class, 'create']);
        Route::get('/vehicles', [VehicleController::class, 'index']);
        Route::get('/vehicles/available', [VehicleController::class, 'availableForRental']);
        Route::get('/vehicles/{id}', [VehicleController::class, 'show']);
        Route::put('/vehicles/{id}', [VehicleController::class, 'update']);
        Route::put('/vehicles/{id}/status', [VehicleController::class, 'updateStatus']);
        Route::put('/vehicles/{id}/mileage', [VehicleController::class, 'updateMileage']);
        Route::delete('/vehicles/{id}', [VehicleController::class, 'delete']);

        // Document routes
        Route::post('/documents', [AssetDocumentController::class, 'create']);
        Route::get('/documents', [AssetDocumentController::class, 'index']);
        Route::get('/documents/expiring', [AssetDocumentController::class, 'expiring']);
        Route::get('/documents/{id}', [AssetDocumentController::class, 'show']);
        Route::put('/documents/{id}', [AssetDocumentController::class, 'update']);
        Route::delete('/documents/{id}', [AssetDocumentController::class, 'delete']);

        // Depreciation routes
        Route::get('/depreciation', [AssetDepreciationController::class, 'index']);
        Route::get('/depreciation/pending', [AssetDepreciationController::class, 'pending']);
        Route::post('/depreciation/{id}/post', [AssetDepreciationController::class, 'post']);
};

Route::middleware(['api', 'auth.configured', 'resolve.tenant'])
    ->prefix('api/v1/assets')
    ->group($registerRoutes);

Route::middleware(['api', 'auth.configured', 'resolve.tenant'])
    ->prefix('api/assets')
    ->group($registerRoutes);
