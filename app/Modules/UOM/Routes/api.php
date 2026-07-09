<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\UOM\Constants\UomPermission;
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
$permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');
$requires = static fn (string $permission): string => $permissionMiddleware.':'.$permission;

$middleware = [
    'api',
    'auth:'.$protectedGuard,
    $currentUserMiddleware,
    $currentTenantMiddleware,
    $currentOrganizationUnitMiddleware,
];

Route::prefix('api/v1')
    ->middleware($middleware)
    ->name('api.v1.')
    ->group(function () use ($requires): void {
        Route::get('uoms/lookup', [UnitOfMeasureController::class, 'lookup'])
            ->middleware($requires(UomPermission::UOMS_VIEW))
            ->name('uoms.lookup');
        Route::get('uoms/base', [UnitOfMeasureController::class, 'base'])
            ->middleware($requires(UomPermission::UOMS_VIEW))
            ->name('uoms.base');
        Route::get('uoms/categories', [UnitOfMeasureController::class, 'categories'])
            ->middleware($requires(UomPermission::UOMS_VIEW))
            ->name('uoms.categories');
        Route::get('uoms/types', [UnitOfMeasureController::class, 'types'])
            ->middleware($requires(UomPermission::UOMS_VIEW))
            ->name('uoms.types');
        Route::patch('uoms/{uom}/activate', [UnitOfMeasureController::class, 'activate'])
            ->whereNumber('uom')
            ->middleware($requires(UomPermission::UOMS_ACTIVATE))
            ->name('uoms.activate');
        Route::patch('uoms/{uom}/deactivate', [UnitOfMeasureController::class, 'deactivate'])
            ->whereNumber('uom')
            ->middleware($requires(UomPermission::UOMS_DEACTIVATE))
            ->name('uoms.deactivate');
        Route::get('uoms', [UnitOfMeasureController::class, 'index'])
            ->middleware($requires(UomPermission::UOMS_VIEW))
            ->name('uoms.index');
        Route::post('uoms', [UnitOfMeasureController::class, 'store'])
            ->middleware($requires(UomPermission::UOMS_CREATE))
            ->name('uoms.store');
        Route::get('uoms/{uom}', [UnitOfMeasureController::class, 'show'])
            ->whereNumber('uom')
            ->middleware($requires(UomPermission::UOMS_VIEW))
            ->name('uoms.show');
        Route::match(['put', 'patch'], 'uoms/{uom}', [UnitOfMeasureController::class, 'update'])
            ->whereNumber('uom')
            ->middleware($requires(UomPermission::UOMS_UPDATE))
            ->name('uoms.update');
        Route::delete('uoms/{uom}', [UnitOfMeasureController::class, 'destroy'])
            ->whereNumber('uom')
            ->middleware($requires(UomPermission::UOMS_DELETE))
            ->name('uoms.destroy');

        Route::post('uom-conversions/convert', ConvertUomController::class)
            ->middleware($requires(UomPermission::CONVERSIONS_RUN))
            ->name('uom-conversions.convert');
        Route::patch('uom-conversions/{uom_conversion}/activate', [UomConversionController::class, 'activate'])
            ->whereNumber('uom_conversion')
            ->middleware($requires(UomPermission::CONVERSIONS_ACTIVATE))
            ->name('uom-conversions.activate');
        Route::patch('uom-conversions/{uom_conversion}/deactivate', [UomConversionController::class, 'deactivate'])
            ->whereNumber('uom_conversion')
            ->middleware($requires(UomPermission::CONVERSIONS_DEACTIVATE))
            ->name('uom-conversions.deactivate');
        Route::get('uom-conversions', [UomConversionController::class, 'index'])
            ->middleware($requires(UomPermission::CONVERSIONS_VIEW))
            ->name('uom-conversions.index');
        Route::post('uom-conversions', [UomConversionController::class, 'store'])
            ->middleware($requires(UomPermission::CONVERSIONS_CREATE))
            ->name('uom-conversions.store');
        Route::get('uom-conversions/{uom_conversion}', [UomConversionController::class, 'show'])
            ->whereNumber('uom_conversion')
            ->middleware($requires(UomPermission::CONVERSIONS_VIEW))
            ->name('uom-conversions.show');
        Route::match(['put', 'patch'], 'uom-conversions/{uom_conversion}', [UomConversionController::class, 'update'])
            ->whereNumber('uom_conversion')
            ->middleware($requires(UomPermission::CONVERSIONS_UPDATE))
            ->name('uom-conversions.update');
        Route::delete('uom-conversions/{uom_conversion}', [UomConversionController::class, 'destroy'])
            ->whereNumber('uom_conversion')
            ->middleware($requires(UomPermission::CONVERSIONS_DELETE))
            ->name('uom-conversions.destroy');
    });
