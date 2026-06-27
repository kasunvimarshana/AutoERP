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
).':required';

$middleware = [
    'api',
    'auth:'.$protectedGuard,
    $currentUserMiddleware,
    $currentTenantMiddleware,
    'tenant.feature:uom',
    'tenant.permission:uom.view',
    $currentOrganizationUnitMiddleware,
];

Route::prefix('api/v1')
    ->middleware($middleware)
    ->name('api.v1.')
    ->group(function (): void {
        Route::get('uoms/lookup', [UnitOfMeasureController::class, 'lookup'])->name('uoms.lookup');
        Route::get('uoms/base', [UnitOfMeasureController::class, 'base'])->name('uoms.base');
        Route::get('uoms/categories', [UnitOfMeasureController::class, 'categories'])->name('uoms.categories');
        Route::get('uoms/types', [UnitOfMeasureController::class, 'types'])->name('uoms.types');
        Route::patch('uoms/{uom}/activate', [UnitOfMeasureController::class, 'activate'])
            ->whereNumber('uom')
            ->middleware('tenant.permission:uom.manage')
            ->name('uoms.activate');
        Route::patch('uoms/{uom}/deactivate', [UnitOfMeasureController::class, 'deactivate'])
            ->whereNumber('uom')
            ->middleware('tenant.permission:uom.manage')
            ->name('uoms.deactivate');
        Route::apiResource('uoms', UnitOfMeasureController::class)->only(['index', 'show'])
            ->parameters(['uoms' => 'uom']);
        Route::apiResource('uoms', UnitOfMeasureController::class)->except(['index', 'show'])
            ->parameters(['uoms' => 'uom'])
            ->middleware('tenant.permission:uom.manage');

        Route::post('uom-conversions/convert', ConvertUomController::class)->middleware('tenant.permission:uom.convert')->name('uom-conversions.convert');
        Route::patch('uom-conversions/{uom_conversion}/activate', [UomConversionController::class, 'activate'])
            ->whereNumber('uom_conversion')
            ->middleware('tenant.permission:uom.manage')
            ->name('uom-conversions.activate');
        Route::patch('uom-conversions/{uom_conversion}/deactivate', [UomConversionController::class, 'deactivate'])
            ->whereNumber('uom_conversion')
            ->middleware('tenant.permission:uom.manage')
            ->name('uom-conversions.deactivate');
        Route::apiResource('uom-conversions', UomConversionController::class)->only(['index', 'show']);
        Route::apiResource('uom-conversions', UomConversionController::class)->except(['index', 'show'])
            ->middleware('tenant.permission:uom.manage');
    });
