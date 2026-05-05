<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class InventoryStockReservationRoutesTest extends TestCase
{
    private static bool $passportKeysPrepared = false;

    public function test_inventory_stock_reservation_named_routes_are_registered(): void
    {
        $this->assertTrue(app('router')->has('inventory.stock-reservations.index'));
        $this->assertTrue(app('router')->has('inventory.stock-reservations.store'));
        $this->assertTrue(app('router')->has('inventory.stock-reservations.show'));
        $this->assertTrue(app('router')->has('inventory.stock-reservations.destroy'));
        $this->assertTrue(app('router')->has('inventory.stock-reservations.release-expired'));
    }

    public function test_inventory_stock_reservation_endpoints_require_authentication(): void
    {
        $this->preparePassportKeys();

        $this->getJson('/api/inventory/stock-reservations')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/inventory/stock-reservations', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/inventory/stock-reservations/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->deleteJson('/api/inventory/stock-reservations/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/inventory/stock-reservations/release-expired', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_inventory_stock_reservation_routes_keep_expected_middleware_contract(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/inventory/stock-reservations', 'GET'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/inventory/stock-reservations', 'POST'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/inventory/stock-reservations/{reservation}', 'GET'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/inventory/stock-reservations/{reservation}', 'DELETE'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/inventory/stock-reservations/release-expired', 'POST'), ['auth:api', 'resolve.tenant']);
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
