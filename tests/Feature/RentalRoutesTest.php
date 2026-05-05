<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Tests\TestCase;

class RentalRoutesTest extends TestCase
{
    public function test_rental_routes_are_registered(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertTrue($this->routeExists($routes, 'api/rentals', 'GET'));
        $this->assertTrue($this->routeExists($routes, 'api/rentals', 'POST'));
        $this->assertTrue($this->routeExists($routes, 'api/rentals/{id}', 'GET'));
        $this->assertTrue($this->routeExists($routes, 'api/rentals/{id}', 'PUT'));
        $this->assertTrue($this->routeExists($routes, 'api/rentals/{id}', 'DELETE'));
        $this->assertTrue($this->routeExists($routes, 'api/rentals/{id}/confirm', 'PATCH'));
        $this->assertTrue($this->routeExists($routes, 'api/rentals/{id}/start', 'PATCH'));
        $this->assertTrue($this->routeExists($routes, 'api/rentals/{id}/complete', 'PATCH'));
        $this->assertTrue($this->routeExists($routes, 'api/rentals/{id}/cancel', 'PATCH'));
        $this->assertTrue($this->routeExists($routes, 'api/rentals/{rentalId}/charges', 'GET'));
        $this->assertTrue($this->routeExists($routes, 'api/rentals/{rentalId}/charges', 'POST'));
        $this->assertTrue($this->routeExists($routes, 'api/rentals/{rentalId}/charges/{id}', 'GET'));
        $this->assertTrue($this->routeExists($routes, 'api/rentals/{rentalId}/charges/{id}', 'DELETE'));
    }

    public function test_rental_routes_keep_expected_middleware_contract(): void
    {
        $routes     = app('router')->getRoutes();
        $route      = $this->findRoute($routes, 'api/rentals', 'GET');
        $middleware = $route->gatherMiddleware();

        $this->assertNotEmpty($middleware, 'Route has no middleware');
        $this->assertContains('api', $middleware, 'Route missing api middleware. Actual: ' . json_encode($middleware));
        $this->assertContains('auth.configured', $middleware, 'Route missing auth.configured. Actual: ' . json_encode($middleware));
        $this->assertContains('resolve.tenant', $middleware, 'Route missing resolve.tenant. Actual: ' . json_encode($middleware));
    }

    public function test_rental_routes_require_authentication(): void
    {
        $response = $this->getJson('/api/rentals', ['X-Tenant-ID' => '1']);

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
