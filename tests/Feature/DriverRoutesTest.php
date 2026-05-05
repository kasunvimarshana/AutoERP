<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Tests\TestCase;

class DriverRoutesTest extends TestCase
{
    public function test_driver_routes_are_registered(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertTrue($this->routeExists($routes, 'api/drivers', 'GET'));
        $this->assertTrue($this->routeExists($routes, 'api/drivers', 'POST'));
        $this->assertTrue($this->routeExists($routes, 'api/drivers/{id}', 'GET'));
        $this->assertTrue($this->routeExists($routes, 'api/drivers/{id}', 'PUT'));
        $this->assertTrue($this->routeExists($routes, 'api/drivers/{id}', 'DELETE'));
        $this->assertTrue($this->routeExists($routes, 'api/drivers/available', 'GET'));
        $this->assertTrue($this->routeExists($routes, 'api/drivers/{id}/status', 'PATCH'));
        $this->assertTrue($this->routeExists($routes, 'api/drivers/{driverId}/licenses', 'GET'));
        $this->assertTrue($this->routeExists($routes, 'api/drivers/{driverId}/licenses', 'POST'));
        $this->assertTrue($this->routeExists($routes, 'api/drivers/{driverId}/licenses/{id}', 'GET'));
        $this->assertTrue($this->routeExists($routes, 'api/drivers/{driverId}/licenses/{id}', 'PUT'));
        $this->assertTrue($this->routeExists($routes, 'api/drivers/{driverId}/licenses/{id}', 'DELETE'));
        $this->assertTrue($this->routeExists($routes, 'api/driver-licenses/expiring-soon', 'GET'));
    }

    public function test_driver_routes_keep_expected_middleware_contract(): void
    {
        $routes     = app('router')->getRoutes();
        $route      = $this->findRoute($routes, 'api/drivers', 'GET');
        $middleware = $route->gatherMiddleware();

        $this->assertNotEmpty($middleware, 'Route has no middleware');
        $this->assertContains('api', $middleware, 'Route missing api middleware. Actual: ' . json_encode($middleware));
        $this->assertContains('auth.configured', $middleware, 'Route missing auth.configured. Actual: ' . json_encode($middleware));
        $this->assertContains('resolve.tenant', $middleware, 'Route missing resolve.tenant. Actual: ' . json_encode($middleware));
    }

    public function test_driver_routes_require_authentication(): void
    {
        $response = $this->getJson('/api/drivers', ['X-Tenant-ID' => '1']);

        $this->assertContains($response->status(), [401, 403]);
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

    private function routeExists(mixed $routes, string $uri, string $method): bool
    {
        /** @var Route $route */
        foreach ($routes as $route) {
            if ($route->uri() !== $uri) {
                continue;
            }

            if (! in_array($method, $route->methods(), true)) {
                continue;
            }

            return true;
        }

        return false;
    }
}
