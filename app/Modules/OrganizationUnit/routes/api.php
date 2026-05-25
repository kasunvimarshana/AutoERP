<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\OrganizationUnit\Presentation\Http\Controllers\OrganizationUnitController;
use Modules\OrganizationUnit\Presentation\Http\Controllers\OrganizationUnitDocumentController;
use Modules\OrganizationUnit\Presentation\Http\Controllers\OrganizationUnitSettingController;
use Modules\OrganizationUnit\Presentation\Http\Controllers\OrganizationUnitSettingGroupController;
use Modules\OrganizationUnit\Presentation\Http\Controllers\OrganizationUnitTypeController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);

Route::prefix('api/organization-unit')
    ->middleware([
        'api',
        'auth:' . $protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
    ])
    ->name('organization-unit.')
    ->group(function (): void {
        Route::apiResource('organization-unit-types', OrganizationUnitTypeController::class);
        Route::apiResource('organization-units', OrganizationUnitController::class);
        Route::apiResource('organization-unit-setting-groups', OrganizationUnitSettingGroupController::class);
        Route::apiResource('organization-unit-settings', OrganizationUnitSettingController::class);
        Route::apiResource('organization-unit-documents', OrganizationUnitDocumentController::class);
    });
