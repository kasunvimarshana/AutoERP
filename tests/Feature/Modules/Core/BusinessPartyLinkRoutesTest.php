<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Core;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class BusinessPartyLinkRoutesTest extends TestCase
{
    public function testBusinessPartyLinkRoutesAreRegistered(): void
    {
        self::assertTrue(Route::has('core.business-party-links.index'));
        self::assertTrue(Route::has('core.business-party-links.store'));
        self::assertTrue(Route::has('core.business-party-links.update'));
        self::assertTrue(Route::has('core.business-party-links.deactivate'));
    }

    public function testBusinessPartyLinkRoutesUseContextMiddlewares(): void
    {
        $route = Route::getRoutes()->getByName('core.business-party-links.index');

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
