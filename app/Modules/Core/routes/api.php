<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Core\Presentation\Http\Controllers\BusinessPartyLinkController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);

Route::prefix('api/core')
    ->middleware([
        'api',
        'auth:' . $protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
    ])
    ->name('core.')
    ->group(function (): void {
        Route::post('business-party-links/{businessPartyLink}/deactivate', [BusinessPartyLinkController::class, 'deactivate'])
            ->name('business-party-links.deactivate');
        Route::apiResource('business-party-links', BusinessPartyLinkController::class)
            ->only(['index', 'store', 'update'])
            ->parameters(['business-party-links' => 'businessPartyLink']);
    });
