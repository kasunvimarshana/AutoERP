<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Organization\Http\Controllers\BranchController;
use Modules\Organization\Http\Controllers\OrganizationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    // Organization routes
    Route::prefix('organizations')->group(function () {
        Route::get('search', [OrganizationController::class, 'search']);
        Route::get('/', [OrganizationController::class, 'index']);
        Route::post('/', [OrganizationController::class, 'store']);
        Route::get('{id}', [OrganizationController::class, 'show']);
        Route::put('{id}', [OrganizationController::class, 'update']);
        Route::delete('{id}', [OrganizationController::class, 'destroy']);
    });

    // Branch routes
    Route::prefix('branches')->group(function () {
        Route::get('search', [BranchController::class, 'search']);
        Route::get('nearby', [BranchController::class, 'nearby']);
        Route::get('organization/{organizationId}', [BranchController::class, 'byOrganization']);
        Route::get('{id}/capacity', [BranchController::class, 'checkCapacity']);
        Route::get('/', [BranchController::class, 'index']);
        Route::post('/', [BranchController::class, 'store']);
        Route::get('{id}', [BranchController::class, 'show']);
        Route::put('{id}', [BranchController::class, 'update']);
        Route::delete('{id}', [BranchController::class, 'destroy']);
    });
});
