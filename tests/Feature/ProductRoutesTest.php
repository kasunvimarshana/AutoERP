<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class ProductRoutesTest extends TestCase
{
    private static bool $passportKeysPrepared = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->preparePassportKeys();
    }

    public function test_product_endpoints_require_authentication(): void
    {
        $this->getJson('/api/products')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/products/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/products/search')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_product_routes_keep_expected_middleware_contract(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/products', 'GET'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/products/{product}', 'GET'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/products/search', 'GET'), ['auth.configured', 'resolve.tenant']);
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
