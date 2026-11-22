<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Tests\Feature\Item;

use Illuminate\Support\Facades\Route;
use Modules\Item\Services\ItemAuthorizationService;
use Tests\TestCase;

final class ItemPermissionBoundaryTest extends TestCase
{
    public function test_item_routes_require_granular_tenant_permissions(): void
    {
        $permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');

        $expected = [
            'api.v1.items.lookup' => ItemAuthorizationService::VIEW,
            'api.v1.items.usage-modules' => ItemAuthorizationService::VIEW.','.ItemAuthorizationService::CREATE.','.ItemAuthorizationService::MANAGE_USAGE_RULES,
            'api.v1.items.with-relations.store' => ItemAuthorizationService::CREATE,
            'api.v1.items.activate' => ItemAuthorizationService::ACTIVATE,
            'api.v1.items.deactivate' => ItemAuthorizationService::DEACTIVATE,
            'api.v1.items.base-uom.usage-audit' => ItemAuthorizationService::VIEW,
            'api.v1.items.base-uom.preview' => ItemAuthorizationService::CHANGE_BASE_UOM,
            'api.v1.items.base-uom.apply' => ItemAuthorizationService::CHANGE_BASE_UOM,
            'api.v1.items.base-uom.revisions' => ItemAuthorizationService::VIEW,
            'api.v1.items.units.index' => ItemAuthorizationService::VIEW,
            'api.v1.items.units.store' => ItemAuthorizationService::MANAGE_UNITS,
            'api.v1.items.units.update' => ItemAuthorizationService::MANAGE_UNITS,
            'api.v1.items.units.destroy' => ItemAuthorizationService::MANAGE_UNITS,
            'api.v1.items.variants.index' => ItemAuthorizationService::VIEW,
            'api.v1.items.variants.store' => ItemAuthorizationService::MANAGE_VARIANTS,
            'api.v1.items.variants.update' => ItemAuthorizationService::MANAGE_VARIANTS,
            'api.v1.items.variants.destroy' => ItemAuthorizationService::MANAGE_VARIANTS,
            'api.v1.items.bundles.index' => ItemAuthorizationService::VIEW,
            'api.v1.items.bundles.store' => ItemAuthorizationService::MANAGE_BUNDLES,
            'api.v1.items.bundles.update' => ItemAuthorizationService::MANAGE_BUNDLES,
            'api.v1.items.bundles.destroy' => ItemAuthorizationService::MANAGE_BUNDLES,
            'api.v1.items.prices.index' => ItemAuthorizationService::VIEW,
            'api.v1.items.prices.store' => ItemAuthorizationService::MANAGE_PRICES,
            'api.v1.items.prices.supersede' => ItemAuthorizationService::MANAGE_PRICES,
            'api.v1.items.codes.index' => ItemAuthorizationService::VIEW,
            'api.v1.items.codes.store' => ItemAuthorizationService::MANAGE_CODES,
            'api.v1.items.codes.update' => ItemAuthorizationService::MANAGE_CODES,
            'api.v1.items.codes.destroy' => ItemAuthorizationService::MANAGE_CODES,
            'api.v1.items.usage-rules.index' => ItemAuthorizationService::VIEW,
            'api.v1.items.usage-rules.store' => ItemAuthorizationService::MANAGE_USAGE_RULES,
            'api.v1.items.usage-rules.update' => ItemAuthorizationService::MANAGE_USAGE_RULES,
            'api.v1.items.usage-rules.destroy' => ItemAuthorizationService::MANAGE_USAGE_RULES,
            'api.v1.items.index' => ItemAuthorizationService::VIEW,
            'api.v1.items.store' => ItemAuthorizationService::CREATE,
            'api.v1.items.show' => ItemAuthorizationService::VIEW,
            'api.v1.items.update' => ItemAuthorizationService::UPDATE,
            'api.v1.items.destroy' => ItemAuthorizationService::DELETE,
            'api.v1.item-categories.lookup' => ItemAuthorizationService::VIEW,
            'api.v1.item-categories.index' => ItemAuthorizationService::VIEW,
            'api.v1.item-categories.store' => ItemAuthorizationService::MANAGE_CATEGORIES,
            'api.v1.item-categories.show' => ItemAuthorizationService::VIEW,
            'api.v1.item-categories.update' => ItemAuthorizationService::MANAGE_CATEGORIES,
            'api.v1.item-categories.destroy' => ItemAuthorizationService::MANAGE_CATEGORIES,
            'api.v1.item-brands.lookup' => ItemAuthorizationService::VIEW,
            'api.v1.item-brands.index' => ItemAuthorizationService::VIEW,
            'api.v1.item-brands.store' => ItemAuthorizationService::MANAGE_BRANDS,
            'api.v1.item-brands.show' => ItemAuthorizationService::VIEW,
            'api.v1.item-brands.update' => ItemAuthorizationService::MANAGE_BRANDS,
            'api.v1.item-brands.destroy' => ItemAuthorizationService::MANAGE_BRANDS,
        ];

        foreach ($expected as $routeName => $permission) {
            $route = Route::getRoutes()->getByName($routeName);

            self::assertNotNull($route, "Route [{$routeName}] must exist.");
            self::assertContains(
                $permissionMiddleware.':'.$permission,
                $route->gatherMiddleware(),
                "Route [{$routeName}] must require [{$permission}].",
            );
        }
    }
}
