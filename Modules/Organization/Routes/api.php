<?php

use Illuminate\Support\Facades\Route;
use Modules\Organization\Http\Controllers\Api\OrganizationController;
use Modules\Organization\Http\Controllers\Api\LocationController;
use Modules\Organization\Http\Controllers\Api\OrganizationalUnitController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->group(function () {
    // Organization routes
    Route::apiResource('organizations', OrganizationController::class);
    Route::get('organizations/{id}/children', [OrganizationController::class, 'children']);
    Route::get('organizations/{id}/hierarchy', [OrganizationController::class, 'hierarchy']);
    Route::get('organizations/{id}/descendants', [OrganizationController::class, 'descendants']);

    // Location routes
    Route::apiResource('locations', LocationController::class);
    Route::get('locations/{id}/children', [LocationController::class, 'children']);
    Route::get('organizations/{organizationId}/locations', [LocationController::class, 'byOrganization']);

    // Organizational Unit routes
    Route::apiResource('organizational-units', OrganizationalUnitController::class);
    Route::get('organizational-units/{id}/children', [OrganizationalUnitController::class, 'children']);
    Route::get('organizational-units/{id}/hierarchy', [OrganizationalUnitController::class, 'hierarchy']);
    Route::get('organizational-units/{id}/descendants', [OrganizationalUnitController::class, 'descendants']);
    Route::get('organizations/{organizationId}/units', [OrganizationalUnitController::class, 'organizationTree']);
});
