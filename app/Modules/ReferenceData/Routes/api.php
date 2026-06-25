<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\ReferenceData\Http\Controllers\CountryController;
use Modules\ReferenceData\Http\Controllers\CurrencyController;
use Modules\ReferenceData\Http\Controllers\LanguageController;
use Modules\ReferenceData\Http\Controllers\TimezoneController;

$guard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUser = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenant = (string) config('core.current_tenant.middleware_alias', 'current.tenant');

Route::prefix('api/v1')->middleware(['api', 'auth:'.$guard, $currentUser, $currentTenant])->name('api.v1.')->group(function (): void {
    foreach ([
        'countries' => ['parameter' => 'country', 'controller' => CountryController::class],
        'currencies' => ['parameter' => 'currency', 'controller' => CurrencyController::class],
        'languages' => ['parameter' => 'language', 'controller' => LanguageController::class],
        'timezones' => ['parameter' => 'timezone', 'controller' => TimezoneController::class],
    ] as $path => $resource) {
        $parameter = $resource['parameter'];
        $controller = $resource['controller'];
        Route::get($path.'/lookup', [$controller, 'lookup'])->name($path.'.lookup');
        Route::get($path, [$controller, 'index'])->name($path.'.index');
        Route::post($path, [$controller, 'store'])->name($path.'.store');
        Route::get($path.'/{'.$parameter.'}', [$controller, 'show'])->whereNumber($parameter)->name($path.'.show');
        Route::put($path.'/{'.$parameter.'}', [$controller, 'update'])->whereNumber($parameter)->name($path.'.update');
        Route::patch($path.'/{'.$parameter.'}/status', [$controller, 'setStatus'])->whereNumber($parameter)->name($path.'.status');
    }
});
