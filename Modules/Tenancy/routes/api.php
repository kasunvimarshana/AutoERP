<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Tenancy\Interfaces\Http\Controllers\TenancyController;

/*
|--------------------------------------------------------------------------
| Tenancy Module API Routes
|--------------------------------------------------------------------------
|
| All routes are versioned under /api/v1/tenants
|
*/

Route::middleware('auth:api')->prefix('api/v1')->name('tenants.')->group(function (): void {
    Route::apiResource('tenants', TenancyController::class);
});
