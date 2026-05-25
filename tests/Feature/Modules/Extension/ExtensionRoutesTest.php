<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Extension;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class ExtensionRoutesTest extends TestCase
{
    public function testExtensionRoutesAreRegistered(): void
    {
        self::assertTrue(Route::has('extension.attachments.index'));
        self::assertTrue(Route::has('extension.attachments.store'));
        self::assertTrue(Route::has('extension.attachments.show'));
        self::assertTrue(Route::has('extension.attachments.update'));
        self::assertTrue(Route::has('extension.attachments.destroy'));
        self::assertTrue(Route::has('extension.entity-attributes.index'));
        self::assertTrue(Route::has('extension.entity-attributes.store'));
        self::assertTrue(Route::has('extension.entity-attributes.show'));
        self::assertTrue(Route::has('extension.entity-attributes.update'));
        self::assertTrue(Route::has('extension.entity-attributes.destroy'));
        self::assertTrue(Route::has('extension.comments.index'));
        self::assertTrue(Route::has('extension.comments.store'));
        self::assertTrue(Route::has('extension.comments.show'));
        self::assertTrue(Route::has('extension.comments.update'));
        self::assertTrue(Route::has('extension.comments.destroy'));
    }

    public function testExtensionRoutesUseContextMiddlewares(): void
    {
        $route = Route::getRoutes()->getByName('extension.attachments.index');

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