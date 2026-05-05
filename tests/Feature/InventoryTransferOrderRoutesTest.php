<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class InventoryTransferOrderRoutesTest extends TestCase
{
    private static bool $passportKeysPrepared = false;

    public function test_inventory_transfer_order_named_routes_are_registered(): void
    {
        $this->assertTrue(app('router')->has('inventory.transfer-orders.index'));
        $this->assertTrue(app('router')->has('inventory.transfer-orders.store'));
        $this->assertTrue(app('router')->has('inventory.transfer-orders.show'));
        $this->assertTrue(app('router')->has('inventory.transfer-orders.approve'));
        $this->assertTrue(app('router')->has('inventory.transfer-orders.receive'));
    }

    public function test_inventory_transfer_order_endpoints_require_authentication(): void
    {
        $this->preparePassportKeys();

        $this->getJson('/api/inventory/transfer-orders')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/inventory/transfer-orders', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/inventory/transfer-orders/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/inventory/transfer-orders/1/approve', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/inventory/transfer-orders/1/receive', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_inventory_transfer_order_routes_keep_expected_middleware_contract(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/inventory/transfer-orders', 'GET'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/inventory/transfer-orders', 'POST'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/inventory/transfer-orders/{transferOrder}', 'GET'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/inventory/transfer-orders/{transferOrder}/approve', 'POST'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/inventory/transfer-orders/{transferOrder}/receive', 'POST'), ['auth.configured', 'resolve.tenant']);
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
