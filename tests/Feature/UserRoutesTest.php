<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class UserRoutesTest extends TestCase
{
    private static bool $passportKeysPrepared = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->preparePassportKeys();
    }

    public function test_user_endpoints_require_authentication(): void
    {
        $this->getJson('/api/users')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/users', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/users/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->putJson('/api/users/1', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->deleteJson('/api/users/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/users/1/assign-role', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->patchJson('/api/users/1/preferences', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_profile_endpoints_require_authentication(): void
    {
        $this->getJson('/api/profile')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->patchJson('/api/profile', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/profile/change-password', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->patchJson('/api/profile/preferences', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/profile/devices')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_role_endpoints_require_authentication(): void
    {
        $this->getJson('/api/roles')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/roles', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/roles/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->deleteJson('/api/roles/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->putJson('/api/roles/1/permissions', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_permission_endpoints_require_authentication(): void
    {
        $this->getJson('/api/permissions')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/permissions', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/permissions/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->deleteJson('/api/permissions/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_user_routes_keep_expected_middleware_contract(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/users', 'GET'),
            ['auth.configured', 'resolve.tenant'],
        );
        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/users/{user}', 'GET'),
            ['auth.configured', 'resolve.tenant'],
        );
        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/profile', 'GET'),
            ['auth.configured', 'resolve.tenant'],
        );
        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/roles', 'GET'),
            ['auth.configured', 'resolve.tenant'],
        );
        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/permissions', 'GET'),
            ['auth.configured', 'resolve.tenant'],
        );
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
