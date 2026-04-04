<?php
use Illuminate\Support\Facades\Route;
use Modules\Configuration\Infrastructure\Http\Controllers\SystemSettingController;
use Modules\Configuration\Infrastructure\Http\Controllers\OrganizationUnitController;

Route::prefix('settings/{tenantId}')->group(function () {
    Route::get('/{group}', [SystemSettingController::class, 'getGroup']);
    Route::get('/{group}/{key}', [SystemSettingController::class, 'get']);
    Route::put('/{group}/{key}', [SystemSettingController::class, 'set']);
});

Route::prefix('org-units')->group(function () {
    Route::get('/tree',  [OrganizationUnitController::class, 'tree']);
    Route::get('/', [OrganizationUnitController::class, 'index']);
    Route::post('/', [OrganizationUnitController::class, 'store']);
    Route::get('/{id}', [OrganizationUnitController::class, 'show']);
    Route::patch('/{id}', [OrganizationUnitController::class, 'update']);
    Route::delete('/{id}', [OrganizationUnitController::class, 'destroy']);
});
