<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Sequence;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class SequenceRoutesTest extends TestCase
{
    public function testSequenceRoutesAreRegistered(): void
    {
        self::assertTrue(Route::has('sequence.sequences.index'));
        self::assertTrue(Route::has('sequence.sequences.store'));
        self::assertTrue(Route::has('sequence.sequences.show'));
        self::assertTrue(Route::has('sequence.sequences.update'));
        self::assertTrue(Route::has('sequence.sequences.destroy'));
    }

    public function testSequenceRoutesUseContextMiddlewares(): void
    {
        $route = Route::getRoutes()->getByName('sequence.sequences.index');

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
