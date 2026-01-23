<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Customer\Http\Controllers\CustomerController;
use Modules\Customer\Http\Controllers\VehicleController;
use Modules\Customer\Http\Controllers\VehicleServiceRecordController;

/*
|--------------------------------------------------------------------------
| Customer Module API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the Customer module.
| All routes are prefixed with 'api/v1' and require authentication.
|
*/

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Customer Routes - Non-parameterized routes MUST come before apiResource
    Route::get('customers/search', [CustomerController::class, 'search'])
        ->name('customers.search');

    // Customer CRUD
    Route::apiResource('customers', CustomerController::class);

    // Additional Customer Routes
    Route::get('customers/{id}/vehicles', [CustomerController::class, 'withVehicles'])
        ->name('customers.vehicles');
    Route::get('customers/{id}/statistics', [CustomerController::class, 'statistics'])
        ->name('customers.statistics');

    // Vehicle Routes - Non-parameterized routes MUST come before apiResource
    Route::get('vehicles/search', [VehicleController::class, 'search'])
        ->name('vehicles.search');
    Route::get('vehicles/due-for-service', [VehicleController::class, 'dueForService'])
        ->name('vehicles.due-for-service');
    Route::get('vehicles/expiring-insurance', [VehicleController::class, 'expiringInsurance'])
        ->name('vehicles.expiring-insurance');

    // Vehicle CRUD
    Route::apiResource('vehicles', VehicleController::class);

    // Additional Vehicle Routes
    Route::get('vehicles/{id}/with-relations', [VehicleController::class, 'withRelations'])
        ->name('vehicles.with-relations');
    Route::get('customers/{customerId}/vehicles', [VehicleController::class, 'byCustomer'])
        ->name('vehicles.by-customer');
    Route::patch('vehicles/{id}/mileage', [VehicleController::class, 'updateMileage'])
        ->name('vehicles.update-mileage');
    Route::post('vehicles/{id}/transfer-ownership', [VehicleController::class, 'transferOwnership'])
        ->name('vehicles.transfer-ownership');
    Route::get('vehicles/{id}/statistics', [VehicleController::class, 'serviceStatistics'])
        ->name('vehicles.statistics');

    // Service Record Routes - Non-parameterized routes MUST come before apiResource
    Route::get('service-records/search', [VehicleServiceRecordController::class, 'search'])
        ->name('service-records.search');
    Route::get('service-records/pending', [VehicleServiceRecordController::class, 'pending'])
        ->name('service-records.pending');
    Route::get('service-records/in-progress', [VehicleServiceRecordController::class, 'inProgress'])
        ->name('service-records.in-progress');
    Route::get('service-records/by-branch', [VehicleServiceRecordController::class, 'byBranch'])
        ->name('service-records.by-branch');
    Route::get('service-records/by-service-type', [VehicleServiceRecordController::class, 'byServiceType'])
        ->name('service-records.by-service-type');
    Route::get('service-records/by-status', [VehicleServiceRecordController::class, 'byStatus'])
        ->name('service-records.by-status');
    Route::get('service-records/by-date-range', [VehicleServiceRecordController::class, 'byDateRange'])
        ->name('service-records.by-date-range');

    // Service Record CRUD
    Route::apiResource('service-records', VehicleServiceRecordController::class);

    // Additional Service Record Routes
    Route::get('service-records/{id}/with-relations', [VehicleServiceRecordController::class, 'withRelations'])
        ->name('service-records.with-relations');
    Route::get('vehicles/{vehicleId}/service-records', [VehicleServiceRecordController::class, 'byVehicle'])
        ->name('service-records.by-vehicle');
    Route::get('customers/{customerId}/service-records', [VehicleServiceRecordController::class, 'byCustomer'])
        ->name('service-records.by-customer');
    Route::get('vehicles/{vehicleId}/cross-branch-history', [VehicleServiceRecordController::class, 'crossBranchHistory'])
        ->name('service-records.cross-branch-history');
    Route::get('vehicles/{vehicleId}/history-summary', [VehicleServiceRecordController::class, 'vehicleHistorySummary'])
        ->name('service-records.vehicle-history-summary');
    Route::post('service-records/{id}/complete', [VehicleServiceRecordController::class, 'complete'])
        ->name('service-records.complete');
    Route::post('service-records/{id}/cancel', [VehicleServiceRecordController::class, 'cancel'])
        ->name('service-records.cancel');
    Route::get('vehicles/{vehicleId}/service-statistics', [VehicleServiceRecordController::class, 'vehicleStatistics'])
        ->name('service-records.vehicle-statistics');
    Route::get('customers/{customerId}/service-statistics', [VehicleServiceRecordController::class, 'customerStatistics'])
        ->name('service-records.customer-statistics');
});
