<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Pricing\Presentation\Http\Controllers\CustomerPriceListController;
use Modules\Pricing\Presentation\Http\Controllers\DiscountController;
use Modules\Pricing\Presentation\Http\Controllers\DiscountRuleController;
use Modules\Pricing\Presentation\Http\Controllers\PriceHistoryController;
use Modules\Pricing\Presentation\Http\Controllers\PriceListController;
use Modules\Pricing\Presentation\Http\Controllers\PriceListItemController;
use Modules\Pricing\Presentation\Http\Controllers\PriceResolverController;
use Modules\Pricing\Presentation\Http\Controllers\PricingRuleConditionController;
use Modules\Pricing\Presentation\Http\Controllers\PricingRuleController;
use Modules\Pricing\Presentation\Http\Controllers\PricingTierController;
use Modules\Pricing\Presentation\Http\Controllers\SupplierPriceListController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);

Route::prefix('api/pricing')
    ->middleware([
        'api',
        'auth:'.$protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
    ])
    ->name('pricing.')
    ->group(function (): void {
        Route::post('resolve-price', [PriceResolverController::class, 'resolve'])->name('resolve-price');
        Route::post('discounts/preview-calculate', [PriceResolverController::class, 'previewDiscountCalculation'])
            ->name('discounts.preview-calculate');
        Route::get('price-lists/{price_list}/usage', [PriceListController::class, 'usage'])->name('price-lists.usage');
        Route::patch('price-lists/{price_list}/activate', [PriceListController::class, 'activate'])->name('price-lists.activate');
        Route::patch('price-lists/{price_list}/deactivate', [PriceListController::class, 'deactivate'])->name('price-lists.deactivate');
        Route::get('pricing-rules/{pricing_rule}/usage', [PricingRuleController::class, 'usage'])->name('pricing-rules.usage');
        Route::patch('pricing-rules/{pricing_rule}/activate', [PricingRuleController::class, 'activate'])->name('pricing-rules.activate');
        Route::patch('pricing-rules/{pricing_rule}/deactivate', [PricingRuleController::class, 'deactivate'])->name('pricing-rules.deactivate');
        Route::apiResource('price-lists', PriceListController::class);
        Route::apiResource('price-list-items', PriceListItemController::class);
        Route::apiResource('pricing-rules', PricingRuleController::class);
        Route::apiResource('pricing-rule-conditions', PricingRuleConditionController::class);
        Route::apiResource('discounts', DiscountController::class);
        Route::apiResource('discount-rules', DiscountRuleController::class);
        Route::apiResource('pricing-tiers', PricingTierController::class);
        Route::apiResource('price-histories', PriceHistoryController::class)->only(['index', 'show']);
        Route::apiResource('supplier-price-lists', SupplierPriceListController::class);
        Route::apiResource('customer-price-lists', CustomerPriceListController::class);
    });
