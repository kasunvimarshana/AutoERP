<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\Http\Controllers\ConfigurationController;

$guard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUser = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenant = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganization = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);

Route::prefix('api/v1/configuration')
    ->middleware(['api', 'auth:'.$guard, $currentUser, $currentTenant])
    ->name('api.v1.configuration.')
    ->group(function () use ($currentOrganization): void {
        Route::get('definitions', [ConfigurationController::class, 'definitions'])->name('definitions');
        Route::get('resolved/{key}', [ConfigurationController::class, 'resolved'])
            ->where('key', '[a-z][a-z0-9._-]+')
            ->name('resolved');

        $registerScope = static function (string $path, string $scope): void {
            Route::prefix($path)->group(function () use ($scope): void {
                Route::get('entries', [ConfigurationController::class, 'index'])
                    ->defaults('scope', $scope)
                    ->name($scope.'.index');
                Route::post('entries', [ConfigurationController::class, 'store'])
                    ->defaults('scope', $scope)
                    ->name($scope.'.store');
                Route::get('entries/{key}', [ConfigurationController::class, 'show'])
                    ->where('key', '[a-z][a-z0-9._-]+')
                    ->defaults('scope', $scope)
                    ->name($scope.'.show');
                Route::put('entries/{key}', [ConfigurationController::class, 'update'])
                    ->where('key', '[a-z][a-z0-9._-]+')
                    ->defaults('scope', $scope)
                    ->name($scope.'.update');
                Route::delete('entries/{key}', [ConfigurationController::class, 'destroy'])
                    ->where('key', '[a-z][a-z0-9._-]+')
                    ->defaults('scope', $scope)
                    ->name($scope.'.destroy');
            });
        };

        $registerScope('global', ConfigurationScope::GLOBAL);
        $registerScope('tenant', ConfigurationScope::TENANT);

        Route::middleware($currentOrganization)->group(function () use ($registerScope): void {
            $registerScope('organization', ConfigurationScope::ORGANIZATION_UNIT);
        });
    });
