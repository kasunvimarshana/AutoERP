<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\ReferenceData\Http\Controllers\CountryController;
use Modules\ReferenceData\Http\Controllers\CurrencyController;
use Modules\ReferenceData\Http\Controllers\LanguageController;
use Modules\ReferenceData\Http\Controllers\TimezoneController;

$tenantGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$platformGuard = (string) config('module-auth.platform_protected_route_guard', 'platform-api');
$currentUser = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenant = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$authenticatedLookupGuards = implode(',', array_values(array_unique([
    $tenantGuard,
    $platformGuard,
])));

$catalogs = [
    'countries' => ['parameter' => 'country', 'controller' => CountryController::class],
    'currencies' => ['parameter' => 'currency', 'controller' => CurrencyController::class],
    'languages' => ['parameter' => 'language', 'controller' => LanguageController::class],
    'timezones' => ['parameter' => 'timezone', 'controller' => TimezoneController::class],
];

// Reference catalogues are global SaaS data. Their read-only lookup endpoint is
// shared by authenticated tenant users and platform operators; it must not
// require a tenant context. Mutation remains on the tenant administration path.
Route::prefix('api/v1')
    ->middleware(['api', 'auth:'.$authenticatedLookupGuards, $currentUser])
    ->name('api.v1.')
    ->group(function () use ($catalogs): void {
        foreach ($catalogs as $path => $resource) {
            Route::get($path.'/lookup', [$resource['controller'], 'lookup'])
                ->name($path.'.lookup');
        }
    });

Route::prefix('api/v1')
    ->middleware(['api', 'auth:'.$tenantGuard, $currentUser, $currentTenant])
    ->name('api.v1.')
    ->group(function () use ($catalogs): void {
        foreach ($catalogs as $path => $resource) {
            $parameter = $resource['parameter'];
            $controller = $resource['controller'];

            Route::get($path, [$controller, 'index'])->name($path.'.index');
            Route::post($path, [$controller, 'store'])->name($path.'.store');
            Route::get($path.'/{'.$parameter.'}', [$controller, 'show'])
                ->whereNumber($parameter)
                ->name($path.'.show');
            Route::put($path.'/{'.$parameter.'}', [$controller, 'update'])
                ->whereNumber($parameter)
                ->name($path.'.update');
            Route::patch($path.'/{'.$parameter.'}/status', [$controller, 'setStatus'])
                ->whereNumber($parameter)
                ->name($path.'.status');
        }
    });
