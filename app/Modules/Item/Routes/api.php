<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Item\Http\Controllers\ItemBaseUomController;
use Modules\Item\Http\Controllers\ItemBrandController;
use Modules\Item\Http\Controllers\ItemCategoryController;
use Modules\Item\Http\Controllers\ItemController;
use Modules\Item\Http\Controllers\ItemRelationController;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit'),
];

Route::prefix('api/v1')->middleware($middleware)->name('api.v1.')->group(function (): void {
    Route::get('items/lookup/{kind?}', [ItemController::class, 'lookup'])
        ->whereIn('kind', ['stockable', 'service', 'labour', 'combo', 'package'])
        ->name('items.lookup');
    Route::post('items/with-relations', [ItemController::class, 'storeWithRelations'])->name('items.with-relations.store');

    Route::patch('items/{item}/activate', [ItemController::class, 'activate'])->whereNumber('item')->name('items.activate');
    Route::patch('items/{item}/deactivate', [ItemController::class, 'deactivate'])->whereNumber('item')->name('items.deactivate');
    Route::get('items/{item}/base-uom/usage-audit', [ItemBaseUomController::class, 'usageAudit'])->whereNumber('item')->name('items.base-uom.usage-audit');
    Route::post('items/{item}/base-uom/preview-change', [ItemBaseUomController::class, 'preview'])->whereNumber('item')->name('items.base-uom.preview');
    Route::post('items/{item}/base-uom/apply-change', [ItemBaseUomController::class, 'apply'])->whereNumber('item')->name('items.base-uom.apply');
    Route::get('items/{item}/base-uom/revisions', [ItemBaseUomController::class, 'revisions'])->whereNumber('item')->name('items.base-uom.revisions');

    Route::get('items/{item}/units', [ItemRelationController::class, 'units'])->whereNumber('item')->name('items.units.index');
    Route::post('items/{item}/units', [ItemRelationController::class, 'storeUnit'])->whereNumber('item')->name('items.units.store');
    Route::put('items/{item}/units/{unit}', [ItemRelationController::class, 'updateUnit'])->whereNumber(['item', 'unit'])->name('items.units.update');
    Route::delete('items/{item}/units/{unit}', [ItemRelationController::class, 'deleteUnit'])->whereNumber(['item', 'unit'])->name('items.units.destroy');

    Route::get('items/{item}/variants', [ItemRelationController::class, 'variants'])->whereNumber('item')->name('items.variants.index');
    Route::post('items/{item}/variants', [ItemRelationController::class, 'storeVariant'])->whereNumber('item')->name('items.variants.store');
    Route::put('items/{item}/variants/{variant}', [ItemRelationController::class, 'updateVariant'])->whereNumber(['item', 'variant'])->name('items.variants.update');
    Route::delete('items/{item}/variants/{variant}', [ItemRelationController::class, 'deleteVariant'])->whereNumber(['item', 'variant'])->name('items.variants.destroy');

    Route::get('items/{item}/bundles', [ItemRelationController::class, 'bundles'])->whereNumber('item')->name('items.bundles.index');
    Route::post('items/{item}/bundles', [ItemRelationController::class, 'storeBundle'])->whereNumber('item')->name('items.bundles.store');
    Route::put('items/{item}/bundles/{bundle}', [ItemRelationController::class, 'updateBundle'])->whereNumber(['item', 'bundle'])->name('items.bundles.update');
    Route::delete('items/{item}/bundles/{bundle}', [ItemRelationController::class, 'deleteBundle'])->whereNumber(['item', 'bundle'])->name('items.bundles.destroy');

    Route::get('items/{item}/prices', [ItemRelationController::class, 'prices'])->whereNumber('item')->name('items.prices.index');
    Route::post('items/{item}/prices', [ItemRelationController::class, 'storePrice'])->whereNumber('item')->name('items.prices.store');
    Route::put('items/{item}/prices/{price}', [ItemRelationController::class, 'updatePrice'])->whereNumber(['item', 'price'])->name('items.prices.update');
    Route::delete('items/{item}/prices/{price}', [ItemRelationController::class, 'deletePrice'])->whereNumber(['item', 'price'])->name('items.prices.destroy');

    Route::get('items/{item}/codes', [ItemRelationController::class, 'codes'])->whereNumber('item')->name('items.codes.index');
    Route::post('items/{item}/codes', [ItemRelationController::class, 'storeCode'])->whereNumber('item')->name('items.codes.store');
    Route::put('items/{item}/codes/{code}', [ItemRelationController::class, 'updateCode'])->whereNumber(['item', 'code'])->name('items.codes.update');
    Route::delete('items/{item}/codes/{code}', [ItemRelationController::class, 'deleteCode'])->whereNumber(['item', 'code'])->name('items.codes.destroy');

    Route::get('items/{item}/usage-rules', [ItemRelationController::class, 'usageRules'])->whereNumber('item')->name('items.usage-rules.index');
    Route::post('items/{item}/usage-rules', [ItemRelationController::class, 'storeUsageRule'])->whereNumber('item')->name('items.usage-rules.store');
    Route::put('items/{item}/usage-rules/{rule}', [ItemRelationController::class, 'updateUsageRule'])->whereNumber(['item', 'rule'])->name('items.usage-rules.update');
    Route::delete('items/{item}/usage-rules/{rule}', [ItemRelationController::class, 'deleteUsageRule'])->whereNumber(['item', 'rule'])->name('items.usage-rules.destroy');

    Route::apiResource('items', ItemController::class);

    Route::get('item-categories/lookup', [ItemCategoryController::class, 'lookup'])->name('item-categories.lookup');
    Route::apiResource('item-categories', ItemCategoryController::class);

    Route::get('item-brands/lookup', [ItemBrandController::class, 'lookup'])->name('item-brands.lookup');
    Route::apiResource('item-brands', ItemBrandController::class);
});
