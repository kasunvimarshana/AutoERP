<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\Http\Controllers\ConfigurationController;
use Modules\Tenant\Constants\PlatformPermission;

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

$registerScope = static function (string $path, string $scope, ?string $writeMiddleware = null): void {
    Route::prefix($path)->group(function () use ($scope, $writeMiddleware): void {
        Route::get('entries', [ConfigurationController::class, 'index'])
            ->defaults('scope', $scope)
            ->name($scope.'.index');
        Route::post('entries', [ConfigurationController::class, 'store'])
            ->middleware(array_filter([$writeMiddleware]))
            ->defaults('scope', $scope)
            ->name($scope.'.store');
        Route::get('entries/{key}', [ConfigurationController::class, 'show'])
            ->where('key', '[a-z][a-z0-9._-]+')
            ->defaults('scope', $scope)
            ->name($scope.'.show');
        Route::put('entries/{key}', [ConfigurationController::class, 'update'])
            ->middleware(array_filter([$writeMiddleware]))
            ->where('key', '[a-z][a-z0-9._-]+')
            ->defaults('scope', $scope)
            ->name($scope.'.update');
        Route::delete('entries/{key}', [ConfigurationController::class, 'destroy'])
            ->middleware(array_filter([$writeMiddleware]))
            ->where('key', '[a-z][a-z0-9._-]+')
            ->defaults('scope', $scope)
            ->name($scope.'.destroy');
    });
};

Route::prefix('api/v1/platform/configuration')
    ->middleware([
        'api',
        $platformHost,
        'auth:'.$platformGuard,
        $currentUser,
        $platformOperator,
        'platform.permission:'.PlatformPermission::CONFIGURATION_MANAGE,
    ])
    ->name('api.v1.platform.configuration.')
    ->group(function () use ($registerScope, $platformStepUp): void {
        Route::get('definitions', [ConfigurationController::class, 'definitions'])
            ->defaults('scope', ConfigurationScope::GLOBAL)
            ->name('definitions');
        $registerScope('global', ConfigurationScope::GLOBAL, $platformStepUp);
    });

Route::prefix('api/v1/configuration')
    ->middleware(['api', 'auth:'.$guard, $currentUser, $currentTenant])
    ->name('api.v1.configuration.')
    ->group(function () use ($currentOrganization, $registerScope): void {
        Route::get('definitions', [ConfigurationController::class, 'definitions'])
            ->defaults('scope', ConfigurationScope::TENANT)
            ->name('definitions');
        Route::get('resolved/{key}', [ConfigurationController::class, 'resolved'])
            ->where('key', '[a-z][a-z0-9._-]+')
            ->defaults('scope', ConfigurationScope::TENANT)
            ->name('resolved');

        $registerScope('tenant', ConfigurationScope::TENANT);

        Route::middleware($currentOrganization)->group(function () use ($registerScope): void {
            $registerScope('organization', ConfigurationScope::ORGANIZATION_UNIT);
        });
    });
