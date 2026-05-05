<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class DriverRoutesTest extends TestCase
{
    private static bool $passportKeysPrepared = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->preparePassportKeys();
    }

    public function test_driver_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/drivers')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/drivers')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/v1/drivers', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/v1/drivers/available')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/v1/drivers/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->putJson('/api/v1/drivers/1', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->deleteJson('/api/v1/drivers/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_driver_license_endpoints_require_authentication(): void
    {
        $this->postJson('/api/v1/drivers/1/licenses', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/v1/drivers/1/licenses')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/v1/drivers/licenses/expiring')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->putJson('/api/v1/drivers/licenses/1', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->deleteJson('/api/v1/drivers/licenses/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_driver_availability_endpoints_require_authentication(): void
    {
        $this->postJson('/api/v1/drivers/1/availability', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/v1/drivers/1/availability')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->putJson('/api/v1/drivers/availability/1', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_driver_commission_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/drivers/1/commissions')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/v1/drivers/commissions/pending')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_driver_routes_keep_expected_middleware_contract(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/v1/drivers', 'GET'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/drivers', 'GET'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/v1/drivers/{driverId}/licenses', 'GET'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/v1/drivers/{driverId}/availability', 'GET'), ['auth.configured', 'resolve.tenant']);
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
