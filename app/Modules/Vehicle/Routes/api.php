<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Vehicle\Http\Controllers\VehicleCategoryController;
use Modules\Vehicle\Http\Controllers\VehicleController;
use Modules\Vehicle\Http\Controllers\VehicleMakeController;
use Modules\Vehicle\Http\Controllers\VehicleModelController;
use Modules\Vehicle\Http\Controllers\VehicleOwnershipController;
use Modules\Vehicle\Http\Controllers\VehicleRelationController;
use Modules\Vehicle\Http\Controllers\VehicleTypeController;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit').':required',
    'tenant.feature:vehicle',
];

Route::prefix('api/v1')->middleware($middleware)->name('api.v1.')->group(function (): void {

    Route::prefix('vehicle-ownerships')->name('vehicle-ownerships.')->group(function (): void {
        Route::get('/', [VehicleOwnershipController::class, 'index'])->name('index');
        Route::post('/', [VehicleOwnershipController::class, 'store'])->name('store');
        Route::get('{ownership}', [VehicleOwnershipController::class, 'show'])->whereNumber('ownership')->name('show');
        Route::patch('{ownership}', [VehicleOwnershipController::class, 'update'])->whereNumber('ownership')->name('update');
        Route::post('{ownership}/set-current', [VehicleOwnershipController::class, 'setCurrent'])->whereNumber('ownership')->name('set-current');
        Route::post('{ownership}/clear-current', [VehicleOwnershipController::class, 'clearCurrent'])->whereNumber('ownership')->name('clear-current');
        Route::delete('{ownership}', [VehicleOwnershipController::class, 'destroy'])->whereNumber('ownership')->name('destroy');
    });

    Route::get('vehicles/lookup/{kind?}', [VehicleController::class, 'lookup'])
        ->whereIn('kind', ['active', 'by-customer', 'service-available', 'rental-available'])
        ->name('vehicles.lookup');
    Route::post('vehicles/with-relations', [VehicleController::class, 'storeWithRelations'])
        ->name('vehicles.with-relations.store');
    Route::patch('vehicles/{vehicle}/activate', [VehicleController::class, 'activate'])
        ->whereNumber('vehicle')
        ->name('vehicles.activate');
    Route::patch('vehicles/{vehicle}/deactivate', [VehicleController::class, 'deactivate'])
        ->whereNumber('vehicle')
        ->name('vehicles.deactivate');
    Route::patch('vehicles/{vehicle}/status', [VehicleController::class, 'changeStatus'])
        ->whereNumber('vehicle')
        ->name('vehicles.status');

    Route::prefix('vehicles/{vehicle}')->name('vehicles.')->group(function (): void {
        Route::get('documents', [VehicleRelationController::class, 'documents'])
            ->whereNumber('vehicle')
            ->name('documents.index');
        Route::post('documents', [VehicleRelationController::class, 'storeDocument'])
            ->whereNumber('vehicle')
            ->name('documents.store');
        Route::get('documents/{document}/preview', [VehicleRelationController::class, 'previewDocument'])
            ->whereNumber(['vehicle', 'document'])
            ->name('documents.preview');
        Route::get('documents/{document}/download', [VehicleRelationController::class, 'downloadDocument'])
            ->whereNumber(['vehicle', 'document'])
            ->name('documents.download');
        Route::put('documents/{document}', [VehicleRelationController::class, 'updateDocument'])
            ->whereNumber(['vehicle', 'document'])
            ->name('documents.update');
        Route::delete('documents/{document}', [VehicleRelationController::class, 'destroyDocument'])
            ->whereNumber(['vehicle', 'document'])
            ->name('documents.destroy');


        Route::get('attributes', [VehicleRelationController::class, 'attributes'])
            ->whereNumber('vehicle')
            ->name('attributes.index');
        Route::post('attributes', [VehicleRelationController::class, 'storeAttribute'])
            ->whereNumber('vehicle')
            ->name('attributes.store');
        Route::put('attributes/{attribute}', [VehicleRelationController::class, 'updateAttribute'])
            ->whereNumber(['vehicle', 'attribute'])
            ->name('attributes.update');
        Route::delete('attributes/{attribute}', [VehicleRelationController::class, 'destroyAttribute'])
            ->whereNumber(['vehicle', 'attribute'])
            ->name('attributes.destroy');

        Route::get('status-history', [VehicleRelationController::class, 'statusHistory'])
            ->whereNumber('vehicle')
            ->name('status-history.index');
    });

    Route::apiResource('vehicles', VehicleController::class);

    Route::get('vehicle-makes/lookup', [VehicleMakeController::class, 'lookup'])->name('vehicle-makes.lookup');
    Route::apiResource('vehicle-makes', VehicleMakeController::class);

    Route::get('vehicle-models/lookup', [VehicleModelController::class, 'lookup'])->name('vehicle-models.lookup');
    Route::apiResource('vehicle-models', VehicleModelController::class);

    Route::get('vehicle-types/lookup', [VehicleTypeController::class, 'lookup'])->name('vehicle-types.lookup');
    Route::apiResource('vehicle-types', VehicleTypeController::class);

    Route::get('vehicle-categories/lookup', [VehicleCategoryController::class, 'lookup'])->name('vehicle-categories.lookup');
    Route::apiResource('vehicle-categories', VehicleCategoryController::class);
});
