<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class FuelTrackingRoutesTest extends TestCase
{
    public function test_fuel_log_routes_are_registered(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertTrue($this->routeExists($routes, 'api/fuel-logs', 'GET'));
        $this->assertTrue($this->routeExists($routes, 'api/fuel-logs', 'POST'));
        $this->assertTrue($this->routeExists($routes, 'api/fuel-logs/{id}', 'GET'));
        $this->assertTrue($this->routeExists($routes, 'api/fuel-logs/{id}', 'DELETE'));
        $this->assertTrue($this->routeExists($routes, 'api/vehicles/{vehicleId}/fuel-logs', 'GET'));
        $this->assertTrue($this->routeExists($routes, 'api/drivers/{driverId}/fuel-logs', 'GET'));
    }

    public function test_fuel_log_routes_keep_expected_middleware_contract(): void
    {
        $routes = app('router')->getRoutes();
        $route  = $this->findRoute($routes, 'api/fuel-logs', 'GET');
        $middleware = $route->gatherMiddleware();

        $this->assertNotEmpty($middleware);
        $this->assertContains('api', $middleware, 'Missing api middleware. Actual: ' . json_encode($middleware));
        $this->assertContains('auth.configured', $middleware, 'Missing auth.configured. Actual: ' . json_encode($middleware));
        $this->assertContains('resolve.tenant', $middleware, 'Missing resolve.tenant. Actual: ' . json_encode($middleware));
    }

    public function test_fuel_log_routes_require_authentication(): void
    {
        $response = $this->getJson('/api/fuel-logs', ['X-Tenant-ID' => '1']);

        $this->assertContains($response->status(), [401, 403]);
    }

    private function findRoute(mixed $routes, string $uri, string $method): \Illuminate\Routing\Route
    {
        foreach ($routes as $route) {
            if ($route->uri() === $uri && in_array($method, $route->methods(), true)) {
                return $route;
            }
        }

        $this->fail(sprintf('Route %s %s was not registered.', $method, $uri));
    }

    private function routeExists(mixed $routes, string $uri, string $method): bool
    {
        foreach ($routes as $route) {
            if ($route->uri() === $uri && in_array($method, $route->methods(), true)) {
                return true;
            }
        }

        return false;
    }
}
