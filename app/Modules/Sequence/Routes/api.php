<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Sequence\Http\Controllers\SequenceController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
).':required';

Route::prefix('api/v1/sequence')
    ->middleware([
        'api',
        'auth:'.$protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
    ])
    ->name('api.v1.sequence.')
    ->group(function (): void {
        Route::post('sequences/preview-number', [SequenceController::class, 'previewNumber'])
            ->name('sequences.preview-number');
        Route::post('sequences/generate-number', [SequenceController::class, 'generateNumber'])
            ->name('sequences.generate-number');
        Route::apiResource('sequences', SequenceController::class);
    });
