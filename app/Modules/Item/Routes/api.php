<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Item\Http\Controllers\ItemBaseUomController;
use Modules\Item\Http\Controllers\ItemBrandController;
use Modules\Item\Http\Controllers\ItemCategoryController;
use Modules\Item\Http\Controllers\ItemController;
use Modules\Item\Http\Controllers\ItemRelationController;
use Modules\Item\Services\ItemAuthorizationService;

$permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');
$requires = static fn (string ...$permissions): string => $permissionMiddleware.':'.implode(',', $permissions);

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit').':required',
    'tenant.feature:item',
];

Route::prefix('api/v1')->middleware($middleware)->name('api.v1.')->group(function () use ($requires): void {
    Route::get('items/lookup/{kind?}', [ItemController::class, 'lookup'])
        ->middleware($requires(ItemAuthorizationService::VIEW))
        ->whereIn('kind', ['stock', 'stockable', 'purchase-stock', 'untracked-stockable', 'batch-tracked-stockable', 'service', 'labour', 'combo', 'package', 'bundle-child'])
        ->name('items.lookup');
    Route::get('items/usage-modules', [ItemController::class, 'usageModules'])
        ->middleware($requires(
            ItemAuthorizationService::VIEW,
            ItemAuthorizationService::CREATE,
            ItemAuthorizationService::MANAGE_USAGE_RULES,
        ))
        ->name('items.usage-modules');
    Route::post('items/with-relations', [ItemController::class, 'storeWithRelations'])
        ->middleware($requires(ItemAuthorizationService::CREATE))
        ->name('items.with-relations.store');

    Route::patch('items/{item}/activate', [ItemController::class, 'activate'])
        ->middleware($requires(ItemAuthorizationService::ACTIVATE))
        ->whereNumber('item')
        ->name('items.activate');
    Route::patch('items/{item}/deactivate', [ItemController::class, 'deactivate'])
        ->middleware($requires(ItemAuthorizationService::DEACTIVATE))
        ->whereNumber('item')
        ->name('items.deactivate');
    Route::get('items/{item}/base-uom/usage-audit', [ItemBaseUomController::class, 'usageAudit'])
        ->middleware($requires(ItemAuthorizationService::VIEW))
        ->whereNumber('item')
        ->name('items.base-uom.usage-audit');
    Route::post('items/{item}/base-uom/preview-change', [ItemBaseUomController::class, 'preview'])
        ->middleware($requires(ItemAuthorizationService::CHANGE_BASE_UOM))
        ->whereNumber('item')
        ->name('items.base-uom.preview');
    Route::post('items/{item}/base-uom/apply-change', [ItemBaseUomController::class, 'apply'])
        ->middleware($requires(ItemAuthorizationService::CHANGE_BASE_UOM))
        ->whereNumber('item')
        ->name('items.base-uom.apply');
    Route::get('items/{item}/base-uom/revisions', [ItemBaseUomController::class, 'revisions'])
        ->middleware($requires(ItemAuthorizationService::VIEW))
        ->whereNumber('item')
        ->name('items.base-uom.revisions');

    Route::get('items/{item}/units', [ItemRelationController::class, 'units'])
        ->middleware($requires(ItemAuthorizationService::VIEW))
        ->whereNumber('item')
        ->name('items.units.index');
    Route::post('items/{item}/units', [ItemRelationController::class, 'storeUnit'])
        ->middleware($requires(ItemAuthorizationService::MANAGE_UNITS))
        ->whereNumber('item')
        ->name('items.units.store');
    Route::put('items/{item}/units/{unit}', [ItemRelationController::class, 'updateUnit'])
        ->middleware($requires(ItemAuthorizationService::MANAGE_UNITS))
        ->whereNumber(['item', 'unit'])
        ->name('items.units.update');
    Route::delete('items/{item}/units/{unit}', [ItemRelationController::class, 'deleteUnit'])
        ->middleware($requires(ItemAuthorizationService::MANAGE_UNITS))
        ->whereNumber(['item', 'unit'])
        ->name('items.units.destroy');

    Route::get('items/{item}/variants', [ItemRelationController::class, 'variants'])
        ->middleware($requires(ItemAuthorizationService::VIEW))
        ->whereNumber('item')
        ->name('items.variants.index');
    Route::post('items/{item}/variants', [ItemRelationController::class, 'storeVariant'])
        ->middleware($requires(ItemAuthorizationService::MANAGE_VARIANTS))
        ->whereNumber('item')
        ->name('items.variants.store');
    Route::put('items/{item}/variants/{variant}', [ItemRelationController::class, 'updateVariant'])
        ->middleware($requires(ItemAuthorizationService::MANAGE_VARIANTS))
        ->whereNumber(['item', 'variant'])
        ->name('items.variants.update');
    Route::delete('items/{item}/variants/{variant}', [ItemRelationController::class, 'deleteVariant'])
        ->middleware($requires(ItemAuthorizationService::MANAGE_VARIANTS))
        ->whereNumber(['item', 'variant'])
        ->name('items.variants.destroy');

    Route::get('items/{item}/bundles', [ItemRelationController::class, 'bundles'])
        ->middleware($requires(ItemAuthorizationService::VIEW))
        ->whereNumber('item')
        ->name('items.bundles.index');
    Route::post('items/{item}/bundles', [ItemRelationController::class, 'storeBundle'])
        ->middleware($requires(ItemAuthorizationService::MANAGE_BUNDLES))
        ->whereNumber('item')
        ->name('items.bundles.store');
    Route::put('items/{item}/bundles/{bundle}', [ItemRelationController::class, 'updateBundle'])
        ->middleware($requires(ItemAuthorizationService::MANAGE_BUNDLES))
        ->whereNumber(['item', 'bundle'])
        ->name('items.bundles.update');
    Route::delete('items/{item}/bundles/{bundle}', [ItemRelationController::class, 'deleteBundle'])
        ->middleware($requires(ItemAuthorizationService::MANAGE_BUNDLES))
        ->whereNumber(['item', 'bundle'])
        ->name('items.bundles.destroy');

    Route::get('items/{item}/prices', [ItemRelationController::class, 'prices'])
        ->middleware($requires(ItemAuthorizationService::VIEW))
        ->whereNumber('item')
        ->name('items.prices.index');
    Route::post('items/{item}/prices', [ItemRelationController::class, 'storePrice'])
        ->middleware($requires(ItemAuthorizationService::MANAGE_PRICES))
        ->whereNumber('item')
        ->name('items.prices.store');
    Route::post('items/{item}/prices/{price}/supersede', [ItemRelationController::class, 'supersedePrice'])
        ->middleware($requires(ItemAuthorizationService::MANAGE_PRICES))
        ->whereNumber(['item', 'price'])
        ->name('items.prices.supersede');

    Route::get('items/{item}/codes', [ItemRelationController::class, 'codes'])
        ->middleware($requires(ItemAuthorizationService::VIEW))
        ->whereNumber('item')
        ->name('items.codes.index');
    Route::post('items/{item}/codes', [ItemRelationController::class, 'storeCode'])
        ->middleware($requires(ItemAuthorizationService::MANAGE_CODES))
        ->whereNumber('item')
        ->name('items.codes.store');
    Route::put('items/{item}/codes/{code}', [ItemRelationController::class, 'updateCode'])
        ->middleware($requires(ItemAuthorizationService::MANAGE_CODES))
        ->whereNumber(['item', 'code'])
        ->name('items.codes.update');
    Route::delete('items/{item}/codes/{code}', [ItemRelationController::class, 'deleteCode'])
        ->middleware($requires(ItemAuthorizationService::MANAGE_CODES))
        ->whereNumber(['item', 'code'])
        ->name('items.codes.destroy');

    Route::get('items/{item}/usage-rules', [ItemRelationController::class, 'usageRules'])
        ->middleware($requires(ItemAuthorizationService::VIEW))
        ->whereNumber('item')
        ->name('items.usage-rules.index');
    Route::post('items/{item}/usage-rules', [ItemRelationController::class, 'storeUsageRule'])
        ->middleware($requires(ItemAuthorizationService::MANAGE_USAGE_RULES))
        ->whereNumber('item')
        ->name('items.usage-rules.store');
    Route::put('items/{item}/usage-rules/{rule}', [ItemRelationController::class, 'updateUsageRule'])
        ->middleware($requires(ItemAuthorizationService::MANAGE_USAGE_RULES))
        ->whereNumber(['item', 'rule'])
        ->name('items.usage-rules.update');
    Route::delete('items/{item}/usage-rules/{rule}', [ItemRelationController::class, 'deleteUsageRule'])
        ->middleware($requires(ItemAuthorizationService::MANAGE_USAGE_RULES))
        ->whereNumber(['item', 'rule'])
        ->name('items.usage-rules.destroy');

    Route::get('items', [ItemController::class, 'index'])
        ->middleware($requires(ItemAuthorizationService::VIEW))
        ->name('items.index');
    Route::post('items', [ItemController::class, 'store'])
        ->middleware($requires(ItemAuthorizationService::CREATE))
        ->name('items.store');
    Route::get('items/{item}', [ItemController::class, 'show'])
        ->whereNumber('item')
        ->middleware($requires(ItemAuthorizationService::VIEW))
        ->name('items.show');
    Route::match(['put', 'patch'], 'items/{item}', [ItemController::class, 'update'])
        ->whereNumber('item')
        ->middleware($requires(ItemAuthorizationService::UPDATE))
        ->name('items.update');
    Route::delete('items/{item}', [ItemController::class, 'destroy'])
        ->whereNumber('item')
        ->middleware($requires(ItemAuthorizationService::DELETE))
        ->name('items.destroy');

    Route::get('item-categories/lookup', [ItemCategoryController::class, 'lookup'])
        ->middleware($requires(ItemAuthorizationService::VIEW))
        ->name('item-categories.lookup');
    Route::get('item-categories', [ItemCategoryController::class, 'index'])
        ->middleware($requires(ItemAuthorizationService::VIEW))
        ->name('item-categories.index');
    Route::post('item-categories', [ItemCategoryController::class, 'store'])
        ->middleware($requires(ItemAuthorizationService::MANAGE_CATEGORIES))
        ->name('item-categories.store');
    Route::get('item-categories/{item_category}', [ItemCategoryController::class, 'show'])
        ->whereNumber('item_category')
        ->middleware($requires(ItemAuthorizationService::VIEW))
        ->name('item-categories.show');
    Route::match(['put', 'patch'], 'item-categories/{item_category}', [ItemCategoryController::class, 'update'])
        ->whereNumber('item_category')
        ->middleware($requires(ItemAuthorizationService::MANAGE_CATEGORIES))
        ->name('item-categories.update');
    Route::delete('item-categories/{item_category}', [ItemCategoryController::class, 'destroy'])
        ->whereNumber('item_category')
        ->middleware($requires(ItemAuthorizationService::MANAGE_CATEGORIES))
        ->name('item-categories.destroy');

    Route::get('item-brands/lookup', [ItemBrandController::class, 'lookup'])
        ->middleware($requires(ItemAuthorizationService::VIEW))
        ->name('item-brands.lookup');
    Route::get('item-brands', [ItemBrandController::class, 'index'])
        ->middleware($requires(ItemAuthorizationService::VIEW))
        ->name('item-brands.index');
    Route::post('item-brands', [ItemBrandController::class, 'store'])
        ->middleware($requires(ItemAuthorizationService::MANAGE_BRANDS))
        ->name('item-brands.store');
    Route::get('item-brands/{item_brand}', [ItemBrandController::class, 'show'])
        ->whereNumber('item_brand')
        ->middleware($requires(ItemAuthorizationService::VIEW))
        ->name('item-brands.show');
    Route::match(['put', 'patch'], 'item-brands/{item_brand}', [ItemBrandController::class, 'update'])
        ->whereNumber('item_brand')
        ->middleware($requires(ItemAuthorizationService::MANAGE_BRANDS))
        ->name('item-brands.update');
    Route::delete('item-brands/{item_brand}', [ItemBrandController::class, 'destroy'])
        ->whereNumber('item_brand')
        ->middleware($requires(ItemAuthorizationService::MANAGE_BRANDS))
        ->name('item-brands.destroy');
});
