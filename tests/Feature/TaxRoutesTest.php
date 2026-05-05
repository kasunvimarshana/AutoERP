<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class TaxRoutesTest extends TestCase
{
    private static bool $passportKeysPrepared = false;

    private static bool $routesCleared = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearRoutesCacheOnce();
        $this->preparePassportKeys();
    }

    public function test_tax_endpoints_require_authentication(): void
    {
        $this->getJson('/api/tax/groups')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/tax/groups/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/tax/groups/1/rates')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/tax/groups/1/rules')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/tax/resolve', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/tax/transactions/sales_invoice/1/lines')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/tax/transactions/sales_invoice/1/lines', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_tax_routes_keep_expected_middleware_contract(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/tax/groups', 'GET'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/tax/groups/{taxGroup}', 'GET'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/tax/groups/{taxGroup}/rates', 'GET'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/tax/groups/{taxGroup}/rules', 'GET'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/tax/resolve', 'POST'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/tax/transactions/{referenceType}/{referenceId}/lines', 'GET'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/tax/transactions/{referenceType}/{referenceId}/lines', 'POST'), ['auth.configured', 'resolve.tenant']);
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
