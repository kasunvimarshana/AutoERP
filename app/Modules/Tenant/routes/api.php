<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Infrastructure\Http\Controllers\TenantController;

Route::prefix('api')->middleware(['auth:api'])->group(function (): void {
    Route::apiResource('tenants', TenantController::class);
});
