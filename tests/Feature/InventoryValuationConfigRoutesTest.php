<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Tests\TestCase;

class InventoryValuationConfigRoutesTest extends TestCase
{
    public function test_valuation_config_named_routes_are_registered(): void
    {
        $this->assertTrue(app('router')->has('inventory.valuation-configs.index'));
        $this->assertTrue(app('router')->has('inventory.valuation-configs.store'));
        $this->assertTrue(app('router')->has('inventory.valuation-configs.show'));
        $this->assertTrue(app('router')->has('inventory.valuation-configs.update'));
        $this->assertTrue(app('router')->has('inventory.valuation-configs.destroy'));
        $this->assertTrue(app('router')->has('inventory.valuation-configs.resolve'));
    }

    public function test_valuation_config_routes_use_expected_middleware(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/inventory/valuation-configs', 'GET'),
            ['auth:api', 'resolve.tenant'],
        );

        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/inventory/valuation-configs', 'POST'),
            ['auth:api', 'resolve.tenant'],
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
}
