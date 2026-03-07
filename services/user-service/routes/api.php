<?php

use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Health check (no auth required)
|--------------------------------------------------------------------------
*/
Route::get('/api/v1/health', HealthController::class)->name('health');

/*
|--------------------------------------------------------------------------
| Tenant management (super-admin only)
|--------------------------------------------------------------------------
*/
Route::prefix('api/v1')->middleware(['keycloak.auth', 'rbac:super-admin'])->group(function () {
    Route::apiResource('tenants', TenantController::class);
});

/*
|--------------------------------------------------------------------------
| User management (tenant-scoped)
|--------------------------------------------------------------------------
*/
Route::prefix('api/v1')->middleware(['keycloak.auth', 'tenant'])->group(function () {

    // Viewers and above can list/show
    Route::get('users',        [UserController::class, 'index'])->name('users.index');
    Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');

    // Managers and above can create / update / delete
    Route::middleware('rbac:manager,admin,super-admin')->group(function () {
        Route::post('users',               [UserController::class, 'store'])->name('users.store');
        Route::put('users/{user}',         [UserController::class, 'update'])->name('users.update');
        Route::patch('users/{user}',       [UserController::class, 'update'])->name('users.patch');
        Route::delete('users/{user}',      [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('users/{user}/restore',[UserController::class, 'restore'])->name('users.restore');
    });
});
