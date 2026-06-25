<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Vehicle\Http\Controllers\VehicleCategoryController;
use Modules\Vehicle\Http\Controllers\VehicleController;
use Modules\Vehicle\Http\Controllers\VehicleMakeController;
use Modules\Vehicle\Http\Controllers\VehicleModelController;
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
    Route::get('vehicles/lookup/{kind?}', [VehicleController::class, 'lookup'])
        ->whereIn('kind', ['active', 'by-customer', 'service-available', 'rental-available'])
        ->name('vehicles.lookup');
    Route::post('vehicles/with-relations', [VehicleController::class, 'storeWithRelations'])->name('vehicles.with-relations.store');
    Route::patch('vehicles/{vehicle}/activate', [VehicleController::class, 'activate'])->whereNumber('vehicle')->name('vehicles.activate');
    Route::patch('vehicles/{vehicle}/deactivate', [VehicleController::class, 'deactivate'])->whereNumber('vehicle')->name('vehicles.deactivate');
    Route::patch('vehicles/{vehicle}/status', [VehicleController::class, 'changeStatus'])->whereNumber('vehicle')->name('vehicles.status');

    Route::get('vehicles/{vehicle}/documents', [VehicleRelationController::class, 'documents'])->whereNumber('vehicle');
    Route::post('vehicles/{vehicle}/documents', [VehicleRelationController::class, 'storeDocument'])->whereNumber('vehicle');
    Route::get('vehicles/{vehicle}/documents/{document}/preview', [VehicleRelationController::class, 'previewDocument'])->whereNumber(['vehicle', 'document']);
    Route::get('vehicles/{vehicle}/documents/{document}/download', [VehicleRelationController::class, 'downloadDocument'])->whereNumber(['vehicle', 'document']);
    Route::put('vehicles/{vehicle}/documents/{document}', [VehicleRelationController::class, 'updateDocument'])->whereNumber(['vehicle', 'document']);
    Route::delete('vehicles/{vehicle}/documents/{document}', [VehicleRelationController::class, 'destroyDocument'])->whereNumber(['vehicle', 'document']);

    Route::get('vehicles/{vehicle}/ownerships', [VehicleRelationController::class, 'ownerships'])->whereNumber('vehicle');
    Route::post('vehicles/{vehicle}/ownerships', [VehicleRelationController::class, 'storeOwnership'])->whereNumber('vehicle');
    Route::put('vehicles/{vehicle}/ownerships/{ownership}', [VehicleRelationController::class, 'updateOwnership'])->whereNumber(['vehicle', 'ownership']);
    Route::delete('vehicles/{vehicle}/ownerships/{ownership}', [VehicleRelationController::class, 'destroyOwnership'])->whereNumber(['vehicle', 'ownership']);

    Route::get('vehicles/{vehicle}/attributes', [VehicleRelationController::class, 'attributes'])->whereNumber('vehicle');
    Route::post('vehicles/{vehicle}/attributes', [VehicleRelationController::class, 'storeAttribute'])->whereNumber('vehicle');
    Route::put('vehicles/{vehicle}/attributes/{attribute}', [VehicleRelationController::class, 'updateAttribute'])->whereNumber(['vehicle', 'attribute']);
    Route::delete('vehicles/{vehicle}/attributes/{attribute}', [VehicleRelationController::class, 'destroyAttribute'])->whereNumber(['vehicle', 'attribute']);

    Route::get('vehicles/{vehicle}/status-history', [VehicleRelationController::class, 'statusHistory'])->whereNumber('vehicle');
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
