<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Core\Authorization\PlatformPermission;
use Modules\OrganizationUnit\Constants\OrganizationUnitPermission;
use Modules\OrganizationUnit\Http\Controllers\OrganizationUnitContextController;
use Modules\OrganizationUnit\Http\Controllers\OrganizationUnitController;
use Modules\OrganizationUnit\Http\Controllers\OrganizationUnitDocumentController;
use Modules\OrganizationUnit\Http\Controllers\OrganizationUnitLegalProfileController;
use Modules\OrganizationUnit\Http\Controllers\OrganizationUnitTypeController;
use Modules\OrganizationUnit\Http\Controllers\Platform\PlatformConfigurationOrganizationTargetController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUser = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenant = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnit = (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit');
$platformGuard = (string) config('module-auth.platform_protected_route_guard', 'platform-api');
$platformHost = (string) config('tenant.platform.host_middleware_alias', 'platform.host');
$platformOperator = (string) config('tenant.platform.operator_middleware_alias', 'platform.operator');
$permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');
$requires = static fn (string ...$permissions): string => $permissionMiddleware.':'.implode(',', $permissions);

Route::get('api/v1/platform/configuration-targets/tenants/{tenant}/organization-units', [PlatformConfigurationOrganizationTargetController::class, 'index'])
    ->middleware(['api', $platformHost, 'auth:'.$platformGuard, $currentUser, $platformOperator, 'platform.permission:'.PlatformPermission::CONFIGURATION_VIEW])
    ->whereNumber('tenant');
Route::get('api/v1/platform/configuration-targets/tenants/{tenant}/organization-units/{organizationUnit}', [PlatformConfigurationOrganizationTargetController::class, 'show'])
    ->middleware(['api', $platformHost, 'auth:'.$platformGuard, $currentUser, $platformOperator, 'platform.permission:'.PlatformPermission::CONFIGURATION_VIEW])
    ->whereNumber('tenant')->whereNumber('organizationUnit');

Route::prefix('api/v1')
    ->middleware(['api', 'auth:'.$protectedGuard, $currentUser, $currentTenant, $currentOrganizationUnit.':optional'])
    ->group(function () use ($requires): void {
        Route::get('organization-units/context/options', [OrganizationUnitContextController::class, 'options'])
            ->name('organization-units.context.options');

        Route::get('organization-unit-types', [OrganizationUnitTypeController::class, 'index'])
            ->middleware($requires(OrganizationUnitPermission::TYPES_VIEW))
            ->name('organization-unit-types.index');
        Route::post('organization-unit-types', [OrganizationUnitTypeController::class, 'store'])
            ->middleware($requires(OrganizationUnitPermission::TYPES_MANAGE))
            ->name('organization-unit-types.store');
        Route::get('organization-unit-types/{organization_unit_type}', [OrganizationUnitTypeController::class, 'show'])
            ->middleware($requires(OrganizationUnitPermission::TYPES_VIEW))
            ->name('organization-unit-types.show');
        Route::match(['put', 'patch'], 'organization-unit-types/{organization_unit_type}', [OrganizationUnitTypeController::class, 'update'])
            ->middleware($requires(OrganizationUnitPermission::TYPES_MANAGE))
            ->name('organization-unit-types.update');
        Route::delete('organization-unit-types/{organization_unit_type}', [OrganizationUnitTypeController::class, 'destroy'])
            ->middleware($requires(OrganizationUnitPermission::TYPES_MANAGE))
            ->name('organization-unit-types.destroy');

        Route::get('organization-units', [OrganizationUnitController::class, 'index'])
            ->middleware($requires(OrganizationUnitPermission::VIEW))
            ->name('organization-units.index');
        Route::post('organization-units', [OrganizationUnitController::class, 'store'])
            ->middleware($requires(OrganizationUnitPermission::CREATE))
            ->name('organization-units.store');
        Route::get('organization-units/{organization_unit}', [OrganizationUnitController::class, 'show'])
            ->middleware($requires(OrganizationUnitPermission::VIEW))
            ->name('organization-units.show');
        Route::match(['put', 'patch'], 'organization-units/{organization_unit}', [OrganizationUnitController::class, 'update'])
            ->middleware($requires(OrganizationUnitPermission::UPDATE))
            ->name('organization-units.update');

        Route::get('organization-units/{organizationUnit}/legal-profile', [OrganizationUnitLegalProfileController::class, 'show'])
            ->middleware($requires(OrganizationUnitPermission::VIEW))
            ->whereNumber('organizationUnit')
            ->name('organization-units.legal-profile.show');
        Route::put('organization-units/{organizationUnit}/legal-profile', [OrganizationUnitLegalProfileController::class, 'update'])
            ->middleware($requires(OrganizationUnitPermission::UPDATE))
            ->whereNumber('organizationUnit')
            ->name('organization-units.legal-profile.update');

        Route::patch('organization-units/{organizationUnit}/activate', [OrganizationUnitController::class, 'activate'])
            ->middleware($requires(OrganizationUnitPermission::ACTIVATE))
            ->whereNumber('organizationUnit')
            ->name('organization-units.activate');
        Route::patch('organization-units/{organizationUnit}/deactivate', [OrganizationUnitController::class, 'deactivate'])
            ->middleware($requires(OrganizationUnitPermission::DEACTIVATE))
            ->whereNumber('organizationUnit')
            ->name('organization-units.deactivate');
        Route::patch('organization-units/{organizationUnit}/retire', [OrganizationUnitController::class, 'retire'])
            ->middleware($requires(OrganizationUnitPermission::RETIRE))
            ->whereNumber('organizationUnit')
            ->name('organization-units.retire');

        Route::put('organization-units/{organizationUnit}/logo', [OrganizationUnitController::class, 'replaceLogo'])
            ->middleware($requires(OrganizationUnitPermission::UPDATE))
            ->whereNumber('organizationUnit')
            ->name('organization-units.logo.update');
        Route::delete('organization-units/{organizationUnit}/logo', [OrganizationUnitController::class, 'removeLogo'])
            ->middleware($requires(OrganizationUnitPermission::UPDATE))
            ->whereNumber('organizationUnit')
            ->name('organization-units.logo.destroy');

        Route::get('organization-units/{organizationUnit}/documents/{document}/download', [OrganizationUnitDocumentController::class, 'download'])
            ->middleware($requires(OrganizationUnitPermission::DOCUMENTS_VIEW))
            ->whereNumber(['organizationUnit', 'document'])
            ->name('organization-units.documents.download');

        Route::get('organization-units/{organizationUnit}/documents', [OrganizationUnitDocumentController::class, 'index'])
            ->middleware($requires(OrganizationUnitPermission::DOCUMENTS_VIEW))
            ->whereNumber('organizationUnit')
            ->name('organization-units.documents.index');
        Route::post('organization-units/{organizationUnit}/documents', [OrganizationUnitDocumentController::class, 'store'])
            ->middleware($requires(OrganizationUnitPermission::DOCUMENTS_MANAGE))
            ->whereNumber('organizationUnit')
            ->name('organization-units.documents.store');
        Route::get('organization-units/{organizationUnit}/documents/{document}', [OrganizationUnitDocumentController::class, 'show'])
            ->middleware($requires(OrganizationUnitPermission::DOCUMENTS_VIEW))
            ->whereNumber(['organizationUnit', 'document'])
            ->name('organization-units.documents.show');
        Route::match(['put', 'patch'], 'organization-units/{organizationUnit}/documents/{document}', [OrganizationUnitDocumentController::class, 'update'])
            ->middleware($requires(OrganizationUnitPermission::DOCUMENTS_MANAGE))
            ->whereNumber(['organizationUnit', 'document'])
            ->name('organization-units.documents.update');
        Route::delete('organization-units/{organizationUnit}/documents/{document}', [OrganizationUnitDocumentController::class, 'destroy'])
            ->middleware($requires(OrganizationUnitPermission::DOCUMENTS_MANAGE))
            ->whereNumber(['organizationUnit', 'document'])
            ->name('organization-units.documents.destroy');
    });
