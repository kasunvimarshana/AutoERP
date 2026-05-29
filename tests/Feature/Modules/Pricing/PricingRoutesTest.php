<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class PricingRoutesTest extends TestCase
{
    public function test_pricing_routes_are_registered(): void
    {
        self::assertTrue(Route::has('pricing.price-lists.index'));
        self::assertTrue(Route::has('pricing.price-lists.store'));
        self::assertTrue(Route::has('pricing.price-lists.show'));
        self::assertTrue(Route::has('pricing.price-lists.update'));
        self::assertTrue(Route::has('pricing.price-lists.destroy'));
        self::assertTrue(Route::has('pricing.price-list-items.index'));
        self::assertTrue(Route::has('pricing.price-list-items.store'));
        self::assertTrue(Route::has('pricing.price-list-items.show'));
        self::assertTrue(Route::has('pricing.price-list-items.update'));
        self::assertTrue(Route::has('pricing.price-list-items.destroy'));
        self::assertTrue(Route::has('pricing.supplier-price-lists.index'));
        self::assertTrue(Route::has('pricing.supplier-price-lists.store'));
        self::assertTrue(Route::has('pricing.supplier-price-lists.show'));
        self::assertTrue(Route::has('pricing.supplier-price-lists.update'));
        self::assertTrue(Route::has('pricing.supplier-price-lists.destroy'));
        self::assertTrue(Route::has('pricing.customer-price-lists.index'));
        self::assertTrue(Route::has('pricing.customer-price-lists.store'));
        self::assertTrue(Route::has('pricing.customer-price-lists.show'));
        self::assertTrue(Route::has('pricing.customer-price-lists.update'));
        self::assertTrue(Route::has('pricing.customer-price-lists.destroy'));
        self::assertTrue(Route::has('pricing.discounts.preview-calculate'));
    }

    public function test_pricing_routes_use_context_middlewares(): void
    {
        $route = Route::getRoutes()->getByName('pricing.price-lists.index');

        self::assertNotNull($route);

        $middlewares = $route->gatherMiddleware();
        self::assertContains('auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'), $middlewares);
        self::assertContains((string) config('core.current_user.middleware_alias', 'current.user'), $middlewares);
        self::assertContains((string) config('core.current_tenant.middleware_alias', 'current.tenant'), $middlewares);
        self::assertContains(
            (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit'),
            $middlewares,
        );
    }
}
