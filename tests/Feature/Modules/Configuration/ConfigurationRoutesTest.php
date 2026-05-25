<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Configuration;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class ConfigurationRoutesTest extends TestCase
{
    public function test_configuration_routes_are_registered(): void
    {
        self::assertTrue(Route::has('configuration.entries.index'));
        self::assertTrue(Route::has('configuration.entries.store'));
        self::assertTrue(Route::has('configuration.entries.show'));
        self::assertTrue(Route::has('configuration.entries.update'));
        self::assertTrue(Route::has('configuration.entries.destroy'));
        self::assertTrue(Route::has('configuration.cache.clear'));
        self::assertTrue(Route::has('configuration.countries.index'));
        self::assertTrue(Route::has('configuration.currencies.index'));
        self::assertTrue(Route::has('configuration.languages.index'));
        self::assertTrue(Route::has('configuration.timezones.index'));
    }

    public function test_configuration_routes_use_current_user_current_tenant_and_current_organization_unit_middlewares(): void
    {
        $route = Route::getRoutes()->getByName('configuration.entries.index');

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
