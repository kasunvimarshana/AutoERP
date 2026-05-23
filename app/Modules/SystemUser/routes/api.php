<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\SystemUser\Presentation\Http\Controllers\SystemUserController;

Route::prefix('api/system-user')
    ->middleware('api')
    ->name('system-user.')
    ->group(function (): void {
        Route::prefix('tenants/{tenant}')
            ->name('tenants.')
            ->group(function (): void {
                Route::apiResource('system-users', SystemUserController::class)
                    ->parameters(['system-users' => 'system_user']);
            });
    });
