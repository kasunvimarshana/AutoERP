<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\OrganizationUnit\Http\Controllers\OrganizationUnitContextController;
use Modules\OrganizationUnit\Http\Controllers\OrganizationUnitController;
use Modules\OrganizationUnit\Http\Controllers\OrganizationUnitDocumentController;
use Modules\OrganizationUnit\Http\Controllers\OrganizationUnitTypeController;
use Modules\OrganizationUnit\Http\Controllers\Platform\PlatformConfigurationOrganizationTargetController;
use Modules\User\Constants\PlatformPermission;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUser = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenant = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnit = (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit');
$platformGuard = (string) config('module-auth.platform_protected_route_guard', 'platform-api');
$platformHost = (string) config('tenant.platform.host_middleware_alias', 'platform.host');
$platformOperator = (string) config('tenant.platform.operator_middleware_alias', 'platform.operator');

Route::get('api/v1/platform/configuration-targets/tenants/{tenant}/organization-units', [PlatformConfigurationOrganizationTargetController::class, 'index'])
    ->middleware(['api', $platformHost, 'auth:'.$platformGuard, $currentUser, $platformOperator, 'platform.permission:'.PlatformPermission::CONFIGURATION_VIEW])
    ->whereNumber('tenant');
Route::get('api/v1/platform/configuration-targets/tenants/{tenant}/organization-units/{organizationUnit}', [PlatformConfigurationOrganizationTargetController::class, 'show'])
    ->middleware(['api', $platformHost, 'auth:'.$platformGuard, $currentUser, $platformOperator, 'platform.permission:'.PlatformPermission::CONFIGURATION_VIEW])
    ->whereNumber('tenant')->whereNumber('organizationUnit');

Route::prefix('api/v1')
    ->middleware(['api', 'auth:'.$protectedGuard, $currentUser, $currentTenant, $currentOrganizationUnit.':optional'])
    ->group(function (): void {
        Route::get('organization-units/context/options', [OrganizationUnitContextController::class, 'options']);
        Route::apiResource('organization-unit-types', OrganizationUnitTypeController::class);
        Route::apiResource('organization-units', OrganizationUnitController::class)->except('destroy');
        Route::patch('organization-units/{organizationUnit}/activate', [OrganizationUnitController::class, 'activate']);
        Route::patch('organization-units/{organizationUnit}/deactivate', [OrganizationUnitController::class, 'deactivate']);
        Route::patch('organization-units/{organizationUnit}/retire', [OrganizationUnitController::class, 'retire']);
        Route::put('organization-units/{organizationUnit}/logo', [OrganizationUnitController::class, 'replaceLogo']);
        Route::delete('organization-units/{organizationUnit}/logo', [OrganizationUnitController::class, 'removeLogo']);
        Route::get('organization-units/{organizationUnit}/documents/{document}/download', [OrganizationUnitDocumentController::class, 'download']);
        Route::apiResource('organization-units.documents', OrganizationUnitDocumentController::class)
            ->parameters(['organization-units' => 'organizationUnit', 'documents' => 'document']);
    });
