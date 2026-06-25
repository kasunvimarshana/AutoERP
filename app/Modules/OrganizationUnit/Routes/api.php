<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\OrganizationUnit\Http\Controllers\OrganizationUnitController;
use Modules\OrganizationUnit\Http\Controllers\OrganizationUnitDocumentController;
use Modules\OrganizationUnit\Http\Controllers\OrganizationUnitTypeController;
use Modules\OrganizationUnit\Http\Controllers\Platform\PlatformConfigurationOrganizationTargetController;
use Modules\User\Constants\PlatformPermission;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit');
$platformGuard = (string) config('module-auth.platform_protected_route_guard', 'platform-api');
$platformHost = (string) config('tenant.platform.host_middleware_alias', 'platform.host');
$platformOperator = (string) config('tenant.platform.operator_middleware_alias', 'platform.operator');

Route::get(
    'api/v1/platform/configuration-targets/tenants/{tenant}/organization-units',
    [PlatformConfigurationOrganizationTargetController::class, 'index'],
)->middleware([
    'api',
    $platformHost,
    'auth:'.$platformGuard,
    $currentUserMiddleware,
    $platformOperator,
    'platform.permission:'.PlatformPermission::CONFIGURATION_VIEW,
])->whereNumber('tenant')
    ->name('platform.configuration-targets.organization-units.index');
Route::get(
    'api/v1/platform/configuration-targets/tenants/{tenant}/organization-units/{organizationUnit}',
    [PlatformConfigurationOrganizationTargetController::class, 'show'],
)->middleware([
    'api',
    $platformHost,
    'auth:'.$platformGuard,
    $currentUserMiddleware,
    $platformOperator,
    'platform.permission:'.PlatformPermission::CONFIGURATION_VIEW,
])->whereNumber('tenant')
    ->whereNumber('organizationUnit')
    ->name('platform.configuration-targets.organization-units.show');

Route::prefix('api/organization-unit')
    ->middleware(['api', 'auth:'.$protectedGuard, $currentUserMiddleware, $currentTenantMiddleware, $currentOrganizationUnitMiddleware])
    ->name('organization-unit.')
    ->group(function (): void {
        Route::get('organization-units/resolve', [OrganizationUnitController::class, 'resolve'])->name('organization-units.resolve');
        Route::apiResource('organization-unit-types', OrganizationUnitTypeController::class);
        Route::apiResource('organization-units', OrganizationUnitController::class);
        Route::apiResource('organization-unit-documents', OrganizationUnitDocumentController::class);
        Route::patch('organization-units/{organizationUnit}/activate', [OrganizationUnitController::class, 'activate'])->name('organization-units.activate');
        Route::patch('organization-units/{organizationUnit}/deactivate', [OrganizationUnitController::class, 'deactivate'])->name('organization-units.deactivate');
        Route::post('organization-units/{organizationUnit}/users', [OrganizationUnitController::class, 'assignUser'])->name('organization-units.users.assign');
    });
