<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Auth;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class AuthRoutesTest extends TestCase
{
    public function testAuthRoutesAreRegistered(): void
    {
        self::assertTrue(Route::has('auth.login'));
        self::assertTrue(Route::has('auth.register'));
        self::assertTrue(Route::has('auth.token.issue'));
        self::assertTrue(Route::has('auth.token.refresh'));
        self::assertTrue(Route::has('auth.token.exchange'));
        self::assertTrue(Route::has('auth.token.validate'));
        self::assertTrue(Route::has('auth.logout'));
        self::assertTrue(Route::has('auth.sessions.list'));
        self::assertTrue(Route::has('auth.sessions.revoke'));
        self::assertTrue(Route::has('auth.verification.request'));
        self::assertTrue(Route::has('auth.verification.verify'));
        self::assertTrue(Route::has('auth.client.authorize'));
    }

    public function testProtectedRoutesUseCurrentUserAndCurrentTenantMiddleware(): void
    {
        $route = Route::getRoutes()->getByName('auth.token.issue');

        self::assertNotNull($route);

        $middlewares = $route->gatherMiddleware();
        self::assertContains('auth:' . (string) config('module-auth.protected_route_guard', 'auth-api'), $middlewares);
        self::assertContains((string) config('core.current_user.middleware_alias', 'current.user'), $middlewares);
        self::assertContains((string) config('core.current_tenant.middleware_alias', 'current.tenant'), $middlewares);
    }
}
