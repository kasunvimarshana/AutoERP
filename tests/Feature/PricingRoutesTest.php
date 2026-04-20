<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class PricingRoutesTest extends TestCase
{
    private static bool $passportKeysPrepared = false;

    private static bool $routesCleared = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearRoutesCacheOnce();
        $this->preparePassportKeys();
    }

    public function test_pricing_endpoints_require_authentication(): void
    {
        $this->getJson('/api/pricing/price-lists')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/pricing/price-lists/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/pricing/price-lists/1/items')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/pricing/customers/1/price-lists')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/pricing/suppliers/1/price-lists')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/pricing/resolve', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_pricing_routes_keep_expected_middleware_contract(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/pricing/price-lists', 'GET'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/pricing/price-lists/{priceList}', 'GET'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/pricing/price-lists/{priceList}/items', 'GET'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/pricing/customers/{customer}/price-lists', 'GET'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/pricing/suppliers/{supplier}/price-lists', 'GET'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/pricing/resolve', 'POST'), ['auth:api', 'resolve.tenant']);
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

    private function clearRoutesCacheOnce(): void
    {
        if (self::$routesCleared) {
            return;
        }

        Artisan::call('route:clear');
        self::$routesCleared = true;
    }
}
