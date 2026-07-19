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
use Modules\Vehicle\Services\VehicleAuthorizationService;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit').':required',
    'tenant.feature:vehicle',
];
$permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');
$requires = static fn (string $permission): string => $permissionMiddleware.':'.$permission;

Route::prefix('api/v1')->middleware($middleware)->name('api.v1.')->group(function () use ($requires): void {
    Route::prefix('vehicle-ownerships')->name('vehicle-ownerships.')->group(function () use ($requires): void {
        Route::get('/', [VehicleOwnershipController::class, 'index'])
            ->middleware($requires(VehicleAuthorizationService::VIEW_OWNERSHIPS))
            ->name('index');
        Route::post('/', [VehicleOwnershipController::class, 'store'])
            ->middleware($requires(VehicleAuthorizationService::MANAGE_OWNERSHIPS))
            ->name('store');
        Route::get('{ownership}', [VehicleOwnershipController::class, 'show'])
            ->whereNumber('ownership')
            ->middleware($requires(VehicleAuthorizationService::VIEW_OWNERSHIPS))
            ->name('show');
        Route::patch('{ownership}', [VehicleOwnershipController::class, 'update'])
            ->whereNumber('ownership')
            ->middleware($requires(VehicleAuthorizationService::MANAGE_OWNERSHIPS))
            ->name('update');
        Route::post('{ownership}/set-current', [VehicleOwnershipController::class, 'setCurrent'])
            ->whereNumber('ownership')
            ->middleware($requires(VehicleAuthorizationService::MANAGE_OWNERSHIPS))
            ->name('set-current');
        Route::post('{ownership}/clear-current', [VehicleOwnershipController::class, 'clearCurrent'])
            ->whereNumber('ownership')
            ->middleware($requires(VehicleAuthorizationService::MANAGE_OWNERSHIPS))
            ->name('clear-current');
        Route::delete('{ownership}', [VehicleOwnershipController::class, 'destroy'])
            ->whereNumber('ownership')
            ->middleware($requires(VehicleAuthorizationService::MANAGE_OWNERSHIPS))
            ->name('destroy');
    });

    Route::get('vehicles/lookup/{kind?}', [VehicleController::class, 'lookup'])
        ->whereIn('kind', ['active', 'by-customer', 'service-available'])
        ->middleware($requires(VehicleAuthorizationService::VIEW))
        ->name('vehicles.lookup');
    Route::post('vehicles/with-relations', [VehicleController::class, 'storeWithRelations'])
        ->middleware($requires(VehicleAuthorizationService::CREATE))
        ->name('vehicles.with-relations.store');
    Route::patch('vehicles/{vehicle}/activate', [VehicleController::class, 'activate'])
        ->whereNumber('vehicle')
        ->middleware($requires(VehicleAuthorizationService::CHANGE_STATUS))
        ->name('vehicles.activate');
    Route::patch('vehicles/{vehicle}/deactivate', [VehicleController::class, 'deactivate'])
        ->whereNumber('vehicle')
        ->middleware($requires(VehicleAuthorizationService::CHANGE_STATUS))
        ->name('vehicles.deactivate');
    Route::patch('vehicles/{vehicle}/status', [VehicleController::class, 'changeStatus'])
        ->whereNumber('vehicle')
        ->middleware($requires(VehicleAuthorizationService::CHANGE_STATUS))
        ->name('vehicles.status');

    Route::prefix('vehicles/{vehicle}')->name('vehicles.')->group(function () use ($requires): void {
        Route::get('documents', [VehicleRelationController::class, 'documents'])
            ->whereNumber('vehicle')
            ->middleware($requires(VehicleAuthorizationService::VIEW))
            ->name('documents.index');
        Route::post('documents', [VehicleRelationController::class, 'storeDocument'])
            ->whereNumber('vehicle')
            ->middleware($requires(VehicleAuthorizationService::MANAGE_DOCUMENTS))
            ->name('documents.store');
        Route::get('documents/{document}/preview', [VehicleRelationController::class, 'previewDocument'])
            ->whereNumber(['vehicle', 'document'])
            ->middleware($requires(VehicleAuthorizationService::DOWNLOAD_DOCUMENTS))
            ->name('documents.preview');
        Route::get('documents/{document}/download', [VehicleRelationController::class, 'downloadDocument'])
            ->whereNumber(['vehicle', 'document'])
            ->middleware($requires(VehicleAuthorizationService::DOWNLOAD_DOCUMENTS))
            ->name('documents.download');
        Route::put('documents/{document}', [VehicleRelationController::class, 'updateDocument'])
            ->whereNumber(['vehicle', 'document'])
            ->middleware($requires(VehicleAuthorizationService::MANAGE_DOCUMENTS))
            ->name('documents.update');
        Route::delete('documents/{document}', [VehicleRelationController::class, 'destroyDocument'])
            ->whereNumber(['vehicle', 'document'])
            ->middleware($requires(VehicleAuthorizationService::MANAGE_DOCUMENTS))
            ->name('documents.destroy');

        Route::get('attributes', [VehicleRelationController::class, 'attributes'])
            ->whereNumber('vehicle')
            ->middleware($requires(VehicleAuthorizationService::VIEW))
            ->name('attributes.index');
        Route::post('attributes', [VehicleRelationController::class, 'storeAttribute'])
            ->whereNumber('vehicle')
            ->middleware($requires(VehicleAuthorizationService::MANAGE_ATTRIBUTES))
            ->name('attributes.store');
        Route::put('attributes/{attribute}', [VehicleRelationController::class, 'updateAttribute'])
            ->whereNumber(['vehicle', 'attribute'])
            ->middleware($requires(VehicleAuthorizationService::MANAGE_ATTRIBUTES))
            ->name('attributes.update');
        Route::delete('attributes/{attribute}', [VehicleRelationController::class, 'destroyAttribute'])
            ->whereNumber(['vehicle', 'attribute'])
            ->middleware($requires(VehicleAuthorizationService::MANAGE_ATTRIBUTES))
            ->name('attributes.destroy');

        Route::get('status-history', [VehicleRelationController::class, 'statusHistory'])
            ->whereNumber('vehicle')
            ->middleware($requires(VehicleAuthorizationService::VIEW))
            ->name('status-history.index');
    });

    Route::get('vehicles', [VehicleController::class, 'index'])
        ->middleware($requires(VehicleAuthorizationService::VIEW))
        ->name('vehicles.index');
    Route::post('vehicles', [VehicleController::class, 'store'])
        ->middleware($requires(VehicleAuthorizationService::CREATE))
        ->name('vehicles.store');
    Route::get('vehicles/{vehicle}', [VehicleController::class, 'show'])
        ->whereNumber('vehicle')
        ->middleware($requires(VehicleAuthorizationService::VIEW))
        ->name('vehicles.show');
    Route::match(['put', 'patch'], 'vehicles/{vehicle}', [VehicleController::class, 'update'])
        ->whereNumber('vehicle')
        ->middleware($requires(VehicleAuthorizationService::UPDATE))
        ->name('vehicles.update');
    Route::delete('vehicles/{vehicle}', [VehicleController::class, 'destroy'])
        ->whereNumber('vehicle')
        ->middleware($requires(VehicleAuthorizationService::DELETE))
        ->name('vehicles.destroy');

    Route::get('vehicle-makes/lookup', [VehicleMakeController::class, 'lookup'])
        ->middleware($requires(VehicleAuthorizationService::VIEW))
        ->name('vehicle-makes.lookup');
    Route::get('vehicle-makes', [VehicleMakeController::class, 'index'])
        ->middleware($requires(VehicleAuthorizationService::VIEW))
        ->name('vehicle-makes.index');
    Route::post('vehicle-makes', [VehicleMakeController::class, 'store'])
        ->middleware($requires(VehicleAuthorizationService::UPDATE))
        ->name('vehicle-makes.store');
    Route::get('vehicle-makes/{vehicle_make}', [VehicleMakeController::class, 'show'])
        ->whereNumber('vehicle_make')
        ->middleware($requires(VehicleAuthorizationService::VIEW))
        ->name('vehicle-makes.show');
    Route::match(['put', 'patch'], 'vehicle-makes/{vehicle_make}', [VehicleMakeController::class, 'update'])
        ->whereNumber('vehicle_make')
        ->middleware($requires(VehicleAuthorizationService::UPDATE))
        ->name('vehicle-makes.update');
    Route::delete('vehicle-makes/{vehicle_make}', [VehicleMakeController::class, 'destroy'])
        ->whereNumber('vehicle_make')
        ->middleware($requires(VehicleAuthorizationService::UPDATE))
        ->name('vehicle-makes.destroy');

    Route::get('vehicle-models/lookup', [VehicleModelController::class, 'lookup'])
        ->middleware($requires(VehicleAuthorizationService::VIEW))
        ->name('vehicle-models.lookup');
    Route::get('vehicle-models', [VehicleModelController::class, 'index'])
        ->middleware($requires(VehicleAuthorizationService::VIEW))
        ->name('vehicle-models.index');
    Route::post('vehicle-models', [VehicleModelController::class, 'store'])
        ->middleware($requires(VehicleAuthorizationService::UPDATE))
        ->name('vehicle-models.store');
    Route::get('vehicle-models/{vehicle_model}', [VehicleModelController::class, 'show'])
        ->whereNumber('vehicle_model')
        ->middleware($requires(VehicleAuthorizationService::VIEW))
        ->name('vehicle-models.show');
    Route::match(['put', 'patch'], 'vehicle-models/{vehicle_model}', [VehicleModelController::class, 'update'])
        ->whereNumber('vehicle_model')
        ->middleware($requires(VehicleAuthorizationService::UPDATE))
        ->name('vehicle-models.update');
    Route::delete('vehicle-models/{vehicle_model}', [VehicleModelController::class, 'destroy'])
        ->whereNumber('vehicle_model')
        ->middleware($requires(VehicleAuthorizationService::UPDATE))
        ->name('vehicle-models.destroy');

    Route::get('vehicle-types/lookup', [VehicleTypeController::class, 'lookup'])
        ->middleware($requires(VehicleAuthorizationService::VIEW))
        ->name('vehicle-types.lookup');
    Route::get('vehicle-types', [VehicleTypeController::class, 'index'])
        ->middleware($requires(VehicleAuthorizationService::VIEW))
        ->name('vehicle-types.index');
    Route::post('vehicle-types', [VehicleTypeController::class, 'store'])
        ->middleware($requires(VehicleAuthorizationService::UPDATE))
        ->name('vehicle-types.store');
    Route::get('vehicle-types/{vehicle_type}', [VehicleTypeController::class, 'show'])
        ->whereNumber('vehicle_type')
        ->middleware($requires(VehicleAuthorizationService::VIEW))
        ->name('vehicle-types.show');
    Route::match(['put', 'patch'], 'vehicle-types/{vehicle_type}', [VehicleTypeController::class, 'update'])
        ->whereNumber('vehicle_type')
        ->middleware($requires(VehicleAuthorizationService::UPDATE))
        ->name('vehicle-types.update');
    Route::delete('vehicle-types/{vehicle_type}', [VehicleTypeController::class, 'destroy'])
        ->whereNumber('vehicle_type')
        ->middleware($requires(VehicleAuthorizationService::UPDATE))
        ->name('vehicle-types.destroy');

    Route::get('vehicle-categories/lookup', [VehicleCategoryController::class, 'lookup'])
        ->middleware($requires(VehicleAuthorizationService::VIEW))
        ->name('vehicle-categories.lookup');
    Route::get('vehicle-categories', [VehicleCategoryController::class, 'index'])
        ->middleware($requires(VehicleAuthorizationService::VIEW))
        ->name('vehicle-categories.index');
    Route::post('vehicle-categories', [VehicleCategoryController::class, 'store'])
        ->middleware($requires(VehicleAuthorizationService::UPDATE))
        ->name('vehicle-categories.store');
    Route::get('vehicle-categories/{vehicle_category}', [VehicleCategoryController::class, 'show'])
        ->whereNumber('vehicle_category')
        ->middleware($requires(VehicleAuthorizationService::VIEW))
        ->name('vehicle-categories.show');
    Route::match(['put', 'patch'], 'vehicle-categories/{vehicle_category}', [VehicleCategoryController::class, 'update'])
        ->whereNumber('vehicle_category')
        ->middleware($requires(VehicleAuthorizationService::UPDATE))
        ->name('vehicle-categories.update');
    Route::delete('vehicle-categories/{vehicle_category}', [VehicleCategoryController::class, 'destroy'])
        ->whereNumber('vehicle_category')
        ->middleware($requires(VehicleAuthorizationService::UPDATE))
        ->name('vehicle-categories.destroy');
});
