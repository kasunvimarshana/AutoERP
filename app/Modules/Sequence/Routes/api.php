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
    'tenant.feature:sequence',
    'tenant.permission:sequences.view',
        $currentOrganizationUnitMiddleware,
    ])
    ->name('api.v1.sequence.')
    ->group(function (): void {
        Route::post('sequences/preview-number', [SequenceController::class, 'previewNumber'])
            ->middleware('tenant.permission:sequences.view')
            ->name('sequences.preview-number');
        Route::post('sequences/generate-number', [SequenceController::class, 'generateNumber'])
            ->middleware('tenant.permission:sequences.generate')
            ->name('sequences.generate-number');
        Route::apiResource('sequences', SequenceController::class)->only(['index', 'show']);
        Route::apiResource('sequences', SequenceController::class)->except(['index', 'show'])
            ->middleware('tenant.permission:sequences.manage');
    });
