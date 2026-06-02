<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Item;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class ItemRoutesTest extends TestCase
{
    public function testItemRoutesAreRegistered(): void
    {
        self::assertTrue(Route::has('item.item-categories.index'));
        self::assertTrue(Route::has('item.item-categories.store'));
        self::assertTrue(Route::has('item.item-categories.show'));
        self::assertTrue(Route::has('item.item-categories.update'));
        self::assertTrue(Route::has('item.item-categories.destroy'));
        self::assertTrue(Route::has('item.item-brands.index'));
        self::assertTrue(Route::has('item.item-brands.store'));
        self::assertTrue(Route::has('item.item-brands.show'));
        self::assertTrue(Route::has('item.item-brands.update'));
        self::assertTrue(Route::has('item.item-brands.destroy'));
        self::assertTrue(Route::has('item.item-types.index'));
        self::assertTrue(Route::has('item.items.index'));
        self::assertTrue(Route::has('item.items.store'));
        self::assertTrue(Route::has('item.items.lookup'));
        self::assertTrue(Route::has('item.items.show'));
        self::assertTrue(Route::has('item.items.update'));
        self::assertTrue(Route::has('item.items.destroy'));
        self::assertTrue(Route::has('item.item-attribute-groups.index'));
        self::assertTrue(Route::has('item.item-attribute-groups.store'));
        self::assertTrue(Route::has('item.item-attribute-groups.show'));
        self::assertTrue(Route::has('item.item-attribute-groups.update'));
        self::assertTrue(Route::has('item.item-attribute-groups.destroy'));
        self::assertTrue(Route::has('item.item-attributes.index'));
        self::assertTrue(Route::has('item.item-attributes.store'));
        self::assertTrue(Route::has('item.item-attributes.show'));
        self::assertTrue(Route::has('item.item-attributes.update'));
        self::assertTrue(Route::has('item.item-attributes.destroy'));
        self::assertTrue(Route::has('item.item-attribute-values.index'));
        self::assertTrue(Route::has('item.item-attribute-values.store'));
        self::assertTrue(Route::has('item.item-attribute-values.show'));
        self::assertTrue(Route::has('item.item-attribute-values.update'));
        self::assertTrue(Route::has('item.item-attribute-values.destroy'));
        self::assertTrue(Route::has('item.item-variants.index'));
        self::assertTrue(Route::has('item.item-variants.store'));
        self::assertTrue(Route::has('item.item-variants.show'));
        self::assertTrue(Route::has('item.item-variants.update'));
        self::assertTrue(Route::has('item.item-variants.destroy'));
        self::assertTrue(Route::has('item.item-variant-attributes.index'));
        self::assertTrue(Route::has('item.item-variant-attributes.store'));
        self::assertTrue(Route::has('item.item-variant-attributes.show'));
        self::assertTrue(Route::has('item.item-variant-attributes.update'));
        self::assertTrue(Route::has('item.item-variant-attributes.destroy'));
        self::assertTrue(Route::has('item.item-variant-attribute-values.index'));
        self::assertTrue(Route::has('item.item-variant-attribute-values.store'));
        self::assertTrue(Route::has('item.item-variant-attribute-values.show'));
        self::assertTrue(Route::has('item.item-variant-attribute-values.update'));
        self::assertTrue(Route::has('item.item-variant-attribute-values.destroy'));
        self::assertTrue(Route::has('item.combo-items.index'));
        self::assertTrue(Route::has('item.combo-items.store'));
        self::assertTrue(Route::has('item.combo-items.show'));
        self::assertTrue(Route::has('item.combo-items.update'));
        self::assertTrue(Route::has('item.combo-items.destroy'));
        self::assertTrue(Route::has('item.item-identifiers.index'));
        self::assertTrue(Route::has('item.item-identifiers.store'));
        self::assertTrue(Route::has('item.item-identifiers.show'));
        self::assertTrue(Route::has('item.item-identifiers.update'));
        self::assertTrue(Route::has('item.item-identifiers.destroy'));
    }

    public function testItemRoutesUseContextMiddlewares(): void
    {
        $route = Route::getRoutes()->getByName('item.item-categories.index');

        self::assertNotNull($route);

        $middlewares = $route->gatherMiddleware();
        self::assertContains('auth:' . (string) config('module-auth.protected_route_guard', 'auth-api'), $middlewares);
        self::assertContains((string) config('core.current_user.middleware_alias', 'current.user'), $middlewares);
        self::assertContains((string) config('core.current_tenant.middleware_alias', 'current.tenant'), $middlewares);
        self::assertContains(
            (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit'),
            $middlewares,
        );
    }
}
