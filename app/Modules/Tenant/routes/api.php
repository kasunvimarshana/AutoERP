<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Infrastructure\Http\Controllers\TenantController;

Route::prefix('api')->group(function () {
    Route::apiResource('tenants', TenantController::class);
});
