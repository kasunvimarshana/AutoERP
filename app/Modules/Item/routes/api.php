<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Item\Presentation\Http\Controllers\ItemCategoryController;
use Modules\Item\Presentation\Http\Controllers\ItemBrandController;
use Modules\Item\Presentation\Http\Controllers\ItemController;
use Modules\Item\Presentation\Http\Controllers\ItemAttributeGroupController;
use Modules\Item\Presentation\Http\Controllers\ItemAttributeController;
use Modules\Item\Presentation\Http\Controllers\ItemAttributeValueController;
use Modules\Item\Presentation\Http\Controllers\ItemVariantController;
use Modules\Item\Presentation\Http\Controllers\ItemVariantAttributeController;
use Modules\Item\Presentation\Http\Controllers\ItemVariantAttributeValueController;
use Modules\Item\Presentation\Http\Controllers\ComboItemController;
use Modules\Item\Presentation\Http\Controllers\ItemIdentifierController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);

Route::prefix('api/item')
    ->middleware([
        'api',
        'auth:' . $protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
    ])
    ->name('item.')
    ->group(function (): void {
        Route::apiResource('item-categories', ItemCategoryController::class);
        Route::apiResource('item-brands', ItemBrandController::class);
        Route::apiResource('items', ItemController::class);
        Route::patch('items/{item}/activate', [ItemController::class, 'activate'])->name('items.activate');
        Route::patch('items/{item}/deactivate', [ItemController::class, 'deactivate'])->name('items.deactivate');
        Route::apiResource('item-attribute-groups', ItemAttributeGroupController::class);
        Route::apiResource('item-attributes', ItemAttributeController::class);
        Route::apiResource('item-attribute-values', ItemAttributeValueController::class);
        Route::apiResource('item-variants', ItemVariantController::class);
        Route::apiResource('item-variant-attributes', ItemVariantAttributeController::class);
        Route::apiResource('item-variant-attribute-values', ItemVariantAttributeValueController::class);
        Route::apiResource('combo-items', ComboItemController::class);
        Route::apiResource('item-identifiers', ItemIdentifierController::class);
    });
