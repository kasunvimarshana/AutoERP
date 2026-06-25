<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\Http\Controllers\ConfigurationController;
use Modules\Configuration\Http\Controllers\Platform\PlatformConfigurationController;
use Modules\User\Constants\PlatformPermission;

$guard = (string) config('module-auth.protected_route_guard', 'auth-api');
$platformGuard = (string) config('module-auth.platform_protected_route_guard', 'platform-api');
$currentUser = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenant = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganization = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);
$platformHost = (string) config('tenant.platform.host_middleware_alias', 'platform.host');
$platformOperator = (string) config('tenant.platform.operator_middleware_alias', 'platform.operator');
$platformStepUp = (string) config('module-auth.platform_mfa.middleware_alias', 'platform.step-up');

$registerTenantScope = static function (
    string $path,
    string $scope,
    array $readMiddleware = [],
    array $writeMiddleware = [],
): void {
    Route::prefix($path)->group(function () use ($scope, $readMiddleware, $writeMiddleware): void {
        Route::get('entries', [ConfigurationController::class, 'index'])
            ->middleware($readMiddleware)
            ->defaults('scope', $scope)
            ->name($scope.'.index');
        Route::post('entries', [ConfigurationController::class, 'store'])
            ->middleware($writeMiddleware)
            ->defaults('scope', $scope)
            ->name($scope.'.store');
        Route::get('entries/{key}', [ConfigurationController::class, 'show'])
            ->middleware($readMiddleware)
            ->where('key', '[a-z][a-z0-9._-]+')
            ->defaults('scope', $scope)
            ->name($scope.'.show');
        Route::put('entries/{key}', [ConfigurationController::class, 'update'])
            ->middleware($writeMiddleware)
            ->where('key', '[a-z][a-z0-9._-]+')
            ->defaults('scope', $scope)
            ->name($scope.'.update');
        Route::get('entries/{key}/history', [ConfigurationController::class, 'history'])
            ->middleware($readMiddleware)
            ->where('key', '[a-z][a-z0-9._-]+')
            ->defaults('scope', $scope)
            ->name($scope.'.history');
        Route::post('entries/{key}/rollback', [ConfigurationController::class, 'rollback'])
            ->middleware($writeMiddleware)
            ->where('key', '[a-z][a-z0-9._-]+')
            ->defaults('scope', $scope)
            ->name($scope.'.rollback');
        Route::delete('entries/{key}', [ConfigurationController::class, 'destroy'])
            ->middleware($writeMiddleware)
            ->where('key', '[a-z][a-z0-9._-]+')
            ->defaults('scope', $scope)
            ->name($scope.'.destroy');
    });
};

$registerPlatformScope = static function (
    string $path,
    string $routeName,
    string $scope,
    array $readMiddleware,
    array $writeMiddleware,
): void {
    Route::prefix($path)->name($routeName.'.')->group(
        function () use ($scope, $readMiddleware, $writeMiddleware): void {
            Route::get('entries', [PlatformConfigurationController::class, 'index'])
                ->middleware($readMiddleware)
                ->defaults('scope', $scope)
                ->name('index');
            Route::post('entries', [PlatformConfigurationController::class, 'store'])
                ->middleware($writeMiddleware)
                ->defaults('scope', $scope)
                ->name('store');
            Route::get('entries/{key}', [PlatformConfigurationController::class, 'show'])
                ->middleware($readMiddleware)
                ->where('key', '[a-z][a-z0-9._-]+')
                ->defaults('scope', $scope)
                ->name('show');
            Route::put('entries/{key}', [PlatformConfigurationController::class, 'update'])
                ->middleware($writeMiddleware)
                ->where('key', '[a-z][a-z0-9._-]+')
                ->defaults('scope', $scope)
                ->name('update');
            Route::get('entries/{key}/history', [PlatformConfigurationController::class, 'history'])
                ->middleware($readMiddleware)
                ->where('key', '[a-z][a-z0-9._-]+')
                ->defaults('scope', $scope)
                ->name('history');
            Route::post('entries/{key}/rollback', [PlatformConfigurationController::class, 'rollback'])
                ->middleware($writeMiddleware)
                ->where('key', '[a-z][a-z0-9._-]+')
                ->defaults('scope', $scope)
                ->name('rollback');
            Route::delete('entries/{key}', [PlatformConfigurationController::class, 'destroy'])
                ->middleware($writeMiddleware)
                ->where('key', '[a-z][a-z0-9._-]+')
                ->defaults('scope', $scope)
                ->name('destroy');
        },
    );
};

Route::prefix('api/v1/platform/configuration')
    ->middleware([
        'api',
        $platformHost,
        'auth:'.$platformGuard,
        $currentUser,
        $platformOperator,
    ])
    ->name('api.v1.platform.configuration.')
    ->group(function () use ($registerPlatformScope, $platformStepUp): void {
        $readMiddleware = ['platform.permission:'.PlatformPermission::CONFIGURATION_VIEW];
        $writeMiddleware = [
            $platformStepUp,
            'platform.permission:'.PlatformPermission::CONFIGURATION_MANAGE,
        ];

        Route::get('definitions', [PlatformConfigurationController::class, 'definitions'])
            ->middleware($readMiddleware)
            ->name('definitions');

        Route::get('global/entries/{key}/impact', [PlatformConfigurationController::class, 'impact'])
            ->middleware($readMiddleware)
            ->where('key', '[a-z][a-z0-9._-]+')
            ->name('global.impact');

        Route::get('export', [PlatformConfigurationController::class, 'export'])
            ->middleware($readMiddleware)
            ->name('export');
        Route::post('import/preview', [PlatformConfigurationController::class, 'previewImport'])
            ->middleware($readMiddleware)
            ->name('import.preview');
        Route::post('import/apply', [PlatformConfigurationController::class, 'applyImport'])
            ->middleware($writeMiddleware)
            ->name('import.apply');

        $registerPlatformScope(
            'global',
            'global',
            ConfigurationScope::GLOBAL,
            $readMiddleware,
            $writeMiddleware,
        );
        $registerPlatformScope(
            'tenants/{tenant}',
            'tenant',
            ConfigurationScope::TENANT,
            $readMiddleware,
            $writeMiddleware,
        );
        $registerPlatformScope(
            'tenants/{tenant}/organizations/{organizationUnit}',
            'organization',
            ConfigurationScope::ORGANIZATION_UNIT,
            $readMiddleware,
            $writeMiddleware,
        );

        Route::get('tenants/{tenant}/resolved/{key}', [PlatformConfigurationController::class, 'resolved'])
            ->middleware($readMiddleware)
            ->whereNumber('tenant')
            ->where('key', '[a-z][a-z0-9._-]+')
            ->name('resolved');
    });

Route::prefix('api/v1/configuration')
    ->middleware(['api', 'auth:'.$guard, $currentUser, $currentTenant])
    ->name('api.v1.configuration.')
    ->group(function () use ($currentOrganization, $registerTenantScope): void {
        Route::get('definitions', [ConfigurationController::class, 'definitions'])
            ->defaults('scope', ConfigurationScope::TENANT)
            ->name('definitions');
        Route::get('resolved/{key}', [ConfigurationController::class, 'resolved'])
            ->middleware($currentOrganization.':optional')
            ->where('key', '[a-z][a-z0-9._-]+')
            ->defaults('scope', ConfigurationScope::TENANT)
            ->name('resolved');

        $registerTenantScope('tenant', ConfigurationScope::TENANT);

        Route::middleware($currentOrganization.':required')->group(function () use ($registerTenantScope): void {
            $registerTenantScope('organization', ConfigurationScope::ORGANIZATION_UNIT);
        });
    });
