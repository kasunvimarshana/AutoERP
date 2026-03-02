<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Organisation\Interfaces\Http\Controllers\OrganisationController;

/*
|--------------------------------------------------------------------------
| Organisation Module API Routes
|--------------------------------------------------------------------------
|
| All routes are versioned under /api/v1/organisations
|
*/

Route::middleware('auth:api')->prefix('api/v1')->name('organisations.')->group(function (): void {
    Route::apiResource('organisations', OrganisationController::class);

    // Branch hierarchy
    Route::get('organisations/{orgId}/branches', [OrganisationController::class, 'listBranches'])->name('branches.index');
    Route::post('organisations/{orgId}/branches', [OrganisationController::class, 'createBranch'])->name('branches.store');
    Route::get('branches/{id}', [OrganisationController::class, 'showBranch'])->name('branches.show');
    Route::put('branches/{id}', [OrganisationController::class, 'updateBranch'])->name('branches.update');
    Route::delete('branches/{id}', [OrganisationController::class, 'deleteBranch'])->name('branches.destroy');

    // Location hierarchy
    Route::get('branches/{branchId}/locations', [OrganisationController::class, 'listLocations'])->name('locations.index');
    Route::post('branches/{branchId}/locations', [OrganisationController::class, 'createLocation'])->name('locations.store');
    Route::get('locations/{id}', [OrganisationController::class, 'showLocation'])->name('locations.show');
    Route::put('locations/{id}', [OrganisationController::class, 'updateLocation'])->name('locations.update');
    Route::delete('locations/{id}', [OrganisationController::class, 'deleteLocation'])->name('locations.destroy');

    // Department hierarchy
    Route::get('locations/{locationId}/departments', [OrganisationController::class, 'listDepartments'])->name('departments.index');
    Route::post('locations/{locationId}/departments', [OrganisationController::class, 'createDepartment'])->name('departments.store');
    Route::get('departments/{id}', [OrganisationController::class, 'showDepartment'])->name('departments.show');
    Route::put('departments/{id}', [OrganisationController::class, 'updateDepartment'])->name('departments.update');
    Route::delete('departments/{id}', [OrganisationController::class, 'deleteDepartment'])->name('departments.destroy');
});
