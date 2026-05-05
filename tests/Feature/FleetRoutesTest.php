<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Tests\TestCase;

class FleetRoutesTest extends TestCase
{
    public function test_fleet_routes_are_registered(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertTrue($this->routeExists($routes, 'api/vehicle-types', 'GET'));
        $this->assertTrue($this->routeExists($routes, 'api/vehicle-types', 'POST'));
        $this->assertTrue($this->routeExists($routes, 'api/vehicle-types/{id}', 'GET'));
        $this->assertTrue($this->routeExists($routes, 'api/vehicle-types/{id}', 'PUT'));
        $this->assertTrue($this->routeExists($routes, 'api/vehicle-types/{id}', 'DELETE'));
        $this->assertTrue($this->routeExists($routes, 'api/vehicles', 'GET'));
        $this->assertTrue($this->routeExists($routes, 'api/vehicles', 'POST'));
        $this->assertTrue($this->routeExists($routes, 'api/vehicles/{id}', 'GET'));
        $this->assertTrue($this->routeExists($routes, 'api/vehicles/{id}', 'PUT'));
        $this->assertTrue($this->routeExists($routes, 'api/vehicles/{id}', 'DELETE'));
        $this->assertTrue($this->routeExists($routes, 'api/vehicles/available-for-rental', 'GET'));
        $this->assertTrue($this->routeExists($routes, 'api/vehicles/available-for-service', 'GET'));
        $this->assertTrue($this->routeExists($routes, 'api/vehicles/{id}/state', 'POST'));
        $this->assertTrue($this->routeExists($routes, 'api/vehicles/{vehicleId}/documents', 'GET'));
        $this->assertTrue($this->routeExists($routes, 'api/vehicles/{vehicleId}/documents', 'POST'));
        $this->assertTrue($this->routeExists($routes, 'api/vehicles/{vehicleId}/documents/{id}', 'GET'));
        $this->assertTrue($this->routeExists($routes, 'api/vehicles/{vehicleId}/documents/{id}', 'PUT'));
        $this->assertTrue($this->routeExists($routes, 'api/vehicles/{vehicleId}/documents/{id}', 'DELETE'));
        $this->assertTrue($this->routeExists($routes, 'api/documents/expiring-soon', 'GET'));
    }

    public function test_fleet_routes_keep_expected_middleware_contract(): void
    {
        $routes = app('router')->getRoutes();
        $route = $this->findRoute($routes, 'api/vehicle-types', 'GET');
        $middleware = $route->gatherMiddleware();

        // Check what middleware are actually present
        $this->assertNotEmpty($middleware, 'Route has no middleware');
        $this->assertContains('api', $middleware, 'Route missing api middleware. Actual: ' . json_encode($middleware));
        $this->assertContains('auth.configured', $middleware, 'Route missing auth.configured. Actual: ' . json_encode($middleware));
        $this->assertContains('resolve.tenant', $middleware, 'Route missing resolve.tenant. Actual: ' . json_encode($middleware));
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
