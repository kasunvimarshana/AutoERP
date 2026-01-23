<?php

use App\Modules\RoleManagement\Http\Controllers\RoleController;
use App\Modules\RoleManagement\Http\Controllers\PermissionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Role & Permission Management API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    
    // Role routes
    Route::apiResource('roles', RoleController::class);
    Route::post('roles/{id}/permissions', [RoleController::class, 'assignPermissions']);
    
    // Permission routes
    Route::apiResource('permissions', PermissionController::class);
    Route::get('permissions/grouped/all', [PermissionController::class, 'grouped']);
});
