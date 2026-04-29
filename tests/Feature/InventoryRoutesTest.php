<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class InventoryRoutesTest extends TestCase
{
    private static bool $passportKeysPrepared = false;

    public function test_inventory_named_routes_are_registered(): void
    {
        $this->assertTrue(app('router')->has('inventory.warehouses.movements.index'));
        $this->assertTrue(app('router')->has('inventory.warehouses.movements.store'));
        $this->assertTrue(app('router')->has('inventory.warehouses.stock-levels.index'));
    }

    public function test_inventory_endpoints_require_authentication(): void
    {
        $this->preparePassportKeys();

        $this->getJson('/api/inventory/warehouses/1/movements')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/inventory/warehouses/1/movements', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/inventory/warehouses/1/stock-levels')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_inventory_routes_keep_expected_middleware_contract(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/inventory/warehouses/{warehouse}/movements', 'GET'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/inventory/warehouses/{warehouse}/movements', 'POST'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/inventory/warehouses/{warehouse}/stock-levels', 'GET'), ['auth.configured', 'resolve.tenant']);
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
