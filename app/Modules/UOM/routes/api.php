<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\UOM\Presentation\Http\Controllers\UnitOfMeasureController;
use Modules\UOM\Presentation\Http\Controllers\UomConversionController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);

Route::prefix('api/uom')
    ->middleware([
        'api',
        'auth:' . $protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
    ])
    ->name('uom.')
    ->group(function (): void {
        Route::apiResource('units-of-measure', UnitOfMeasureController::class);
        Route::apiResource('uom-conversions', UomConversionController::class);
    });