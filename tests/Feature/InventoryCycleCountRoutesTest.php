<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class InventoryCycleCountRoutesTest extends TestCase
{
    private static bool $passportKeysPrepared = false;

    public function test_inventory_cycle_count_named_routes_are_registered(): void
    {
        $this->assertTrue(app('router')->has('inventory.cycle-counts.index'));
        $this->assertTrue(app('router')->has('inventory.cycle-counts.store'));
        $this->assertTrue(app('router')->has('inventory.cycle-counts.show'));
        $this->assertTrue(app('router')->has('inventory.cycle-counts.start'));
        $this->assertTrue(app('router')->has('inventory.cycle-counts.complete'));
    }

    public function test_inventory_cycle_count_endpoints_require_authentication(): void
    {
        $this->preparePassportKeys();

        $this->getJson('/api/inventory/cycle-counts')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/inventory/cycle-counts', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/inventory/cycle-counts/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/inventory/cycle-counts/1/start', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/inventory/cycle-counts/1/complete', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_inventory_cycle_count_routes_keep_expected_middleware_contract(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/inventory/cycle-counts', 'GET'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/inventory/cycle-counts', 'POST'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/inventory/cycle-counts/{cycleCount}', 'GET'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/inventory/cycle-counts/{cycleCount}/start', 'POST'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/inventory/cycle-counts/{cycleCount}/complete', 'POST'), ['auth:api', 'resolve.tenant']);
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
