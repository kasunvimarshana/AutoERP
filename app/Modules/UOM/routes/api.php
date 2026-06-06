<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\UOM\Http\Controllers\ConvertUomController;
use Modules\UOM\Http\Controllers\UnitOfMeasureController;
use Modules\UOM\Http\Controllers\UomConversionController;

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
        Route::post('convert', ConvertUomController::class)->name('convert');
        Route::get('categories', [UnitOfMeasureController::class, 'categories'])->name('categories.index');
        Route::get('units-of-measure/{units_of_measure}/usage', [UnitOfMeasureController::class, 'usage'])
            ->whereNumber('units_of_measure')
            ->name('units-of-measure.usage');
        Route::patch('units-of-measure/{units_of_measure}/activate', [UnitOfMeasureController::class, 'activate'])
            ->whereNumber('units_of_measure')
            ->name('units-of-measure.activate');
        Route::patch('units-of-measure/{units_of_measure}/deactivate', [UnitOfMeasureController::class, 'deactivate'])
            ->whereNumber('units_of_measure')
            ->name('units-of-measure.deactivate');
        Route::patch('uom-conversions/{uom_conversion}/activate', [UomConversionController::class, 'activate'])
            ->whereNumber('uom_conversion')
            ->name('uom-conversions.activate');
        Route::patch('uom-conversions/{uom_conversion}/deactivate', [UomConversionController::class, 'deactivate'])
            ->whereNumber('uom_conversion')
            ->name('uom-conversions.deactivate');
        Route::apiResource('units-of-measure', UnitOfMeasureController::class);
        Route::apiResource('uom-conversions', UomConversionController::class);
    });
