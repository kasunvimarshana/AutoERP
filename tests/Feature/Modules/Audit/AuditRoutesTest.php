<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Audit;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class AuditRoutesTest extends TestCase
{
    public function testAuditRoutesAreRegistered(): void
    {
        self::assertTrue(Route::has('audit.audit-logs.index'));
        self::assertTrue(Route::has('audit.audit-logs.show'));
    }

    public function testAuditRoutesUseContextMiddlewares(): void
    {
        $route = Route::getRoutes()->getByName('audit.audit-logs.index');

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
