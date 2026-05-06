<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class SupplierRoutesTest extends TestCase
{
    private static bool $passportKeysPrepared = false;

    private static bool $routesCleared = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearRoutesCacheOnce();
        $this->preparePassportKeys();
    }

    public function test_supplier_endpoints_require_authentication(): void
    {
        $this->getJson('/api/suppliers')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/suppliers/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/suppliers/1/addresses')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/suppliers/1/contacts')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/suppliers/1/products')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_supplier_routes_keep_expected_middleware_contract(): void
    {
        $routes = app('router')->getRoutes();

        $supplierRoutes = [
            ['GET', 'api/suppliers'],
            ['POST', 'api/suppliers'],
            ['GET', 'api/suppliers/{supplier}'],
            ['PUT', 'api/suppliers/{supplier}'],
            ['PATCH', 'api/suppliers/{supplier}'],
            ['DELETE', 'api/suppliers/{supplier}'],
            ['GET', 'api/suppliers/{supplier}/addresses'],
            ['POST', 'api/suppliers/{supplier}/addresses'],
            ['PUT', 'api/suppliers/{supplier}/addresses/{address}'],
            ['DELETE', 'api/suppliers/{supplier}/addresses/{address}'],
            ['GET', 'api/suppliers/{supplier}/contacts'],
            ['POST', 'api/suppliers/{supplier}/contacts'],
            ['PUT', 'api/suppliers/{supplier}/contacts/{contact}'],
            ['DELETE', 'api/suppliers/{supplier}/contacts/{contact}'],
            ['GET', 'api/suppliers/{supplier}/products'],
            ['POST', 'api/suppliers/{supplier}/products'],
            ['PUT', 'api/suppliers/{supplier}/products/{supplierProduct}'],
            ['DELETE', 'api/suppliers/{supplier}/products/{supplierProduct}'],
        ];

        foreach ($supplierRoutes as [$method, $uri]) {
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

    private function clearRoutesCacheOnce(): void
    {
        if (self::$routesCleared) {
            return;
        }

        Artisan::call('route:clear');
        self::$routesCleared = true;
    }
}
