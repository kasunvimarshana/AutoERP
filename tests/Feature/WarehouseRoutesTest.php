<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class WarehouseRoutesTest extends TestCase
{
    private static bool $passportKeysPrepared = false;

    public function test_warehouse_named_routes_are_registered(): void
    {
        $this->assertTrue(app('router')->has('warehouses.index'));
        $this->assertTrue(app('router')->has('warehouses.store'));
        $this->assertTrue(app('router')->has('warehouses.show'));
        $this->assertTrue(app('router')->has('warehouses.update'));
        $this->assertTrue(app('router')->has('warehouses.destroy'));
        $this->assertTrue(app('router')->has('warehouse-locations.index'));
        $this->assertTrue(app('router')->has('warehouse-locations.store'));
        $this->assertTrue(app('router')->has('warehouse-locations.show'));
        $this->assertTrue(app('router')->has('warehouse-locations.update'));
        $this->assertTrue(app('router')->has('warehouse-locations.destroy'));
        $this->assertTrue(app('router')->has('warehouse-stock-movements.index'));
        $this->assertTrue(app('router')->has('warehouse-stock-movements.store'));
        $this->assertTrue(app('router')->has('warehouse-stock-levels.index'));
    }

    public function test_warehouse_endpoints_require_authentication(): void
    {
        $this->preparePassportKeys();

        $this->getJson('/api/warehouses')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/warehouses', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/warehouses/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->putJson('/api/warehouses/1', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->deleteJson('/api/warehouses/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);

        $this->getJson('/api/warehouses/1/locations')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/warehouses/1/locations', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/warehouses/1/locations/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->putJson('/api/warehouses/1/locations/1', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->deleteJson('/api/warehouses/1/locations/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);

        $this->getJson('/api/warehouses/1/stock-movements')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/warehouses/1/stock-movements', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/warehouses/1/stock-levels')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_warehouse_routes_keep_expected_middleware_contract(): void
    {
        $routes = app('router')->getRoutes();

        $warehouseRoutes = [
            ['GET', 'api/warehouses'],
            ['POST', 'api/warehouses'],
            ['GET', 'api/warehouses/{warehouse}'],
            ['PUT', 'api/warehouses/{warehouse}'],
            ['DELETE', 'api/warehouses/{warehouse}'],
            ['GET', 'api/warehouses/{warehouse}/locations'],
            ['POST', 'api/warehouses/{warehouse}/locations'],
            ['GET', 'api/warehouses/{warehouse}/locations/{location}'],
            ['PUT', 'api/warehouses/{warehouse}/locations/{location}'],
            ['DELETE', 'api/warehouses/{warehouse}/locations/{location}'],
            ['GET', 'api/warehouses/{warehouse}/stock-movements'],
            ['POST', 'api/warehouses/{warehouse}/stock-movements'],
            ['GET', 'api/warehouses/{warehouse}/stock-levels'],
        ];

        foreach ($warehouseRoutes as [$method, $uri]) {
            $this->assertRouteUsesMiddleware(
                $this->findRoute($routes, $uri, $method),
                ['auth.configured', 'resolve.tenant']
            );
        }
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
