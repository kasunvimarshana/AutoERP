<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class AuthRoutesTest extends TestCase
{
    private static bool $passportKeysPrepared = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->preparePassportKeys();
    }

    public function test_protected_auth_endpoints_require_authentication(): void
    {
        $this->postJson('/api/auth/logout')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/auth/me')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/auth/refresh')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_auth_routes_keep_expected_middleware_contract(): void
    {
        $routes = app('router')->getRoutes();

        // Protected routes must carry auth.configured
        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/auth/logout', 'POST'),
            ['auth.configured'],
        );
        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/auth/me', 'GET'),
            ['auth.configured'],
        );
        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/auth/refresh', 'POST'),
            ['auth.configured'],
        );

        // Public routes must carry throttle middleware but NOT auth.configured
        $loginRoute = $this->findRoute($routes, 'api/auth/login', 'POST');
        $loginMiddleware = $loginRoute->gatherMiddleware();
        $this->assertNotContains('auth.configured', $loginMiddleware);
        $this->assertNotEmpty(
            array_filter($loginMiddleware, static fn (string $m): bool => str_starts_with($m, 'throttle:')),
        );

        $registerRoute = $this->findRoute($routes, 'api/auth/register', 'POST');
        $registerMiddleware = $registerRoute->gatherMiddleware();
        $this->assertNotContains('auth.configured', $registerMiddleware);
    }

    /**
     * @param  array<int, string>  $expectedMiddleware
     */
    private function assertRouteUsesMiddleware(Route $route, array $expectedMiddleware): void
    {
        $routeMiddleware = $route->gatherMiddleware();

        foreach ($expectedMiddleware as $middleware) {
            $this->assertContains($middleware, $routeMiddleware);
        }
    }

    private function findRoute(mixed $routes, string $uri, string $method): Route
    {
        /** @var Route $route */
        foreach ($routes as $route) {
            if ($route->uri() !== $uri) {
                continue;
            }

            if (! in_array($method, $route->methods(), true)) {
                continue;
            }

            return $route;
        }

        $this->fail(sprintf('Route %s %s was not registered.', $method, $uri));
    }

    private function preparePassportKeys(): void
    {
        if (self::$passportKeysPrepared) {
            return;
        }

        Artisan::call('passport:keys', ['--force' => true]);

        self::$passportKeysPrepared = true;
    }
}
