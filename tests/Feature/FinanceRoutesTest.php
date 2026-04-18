<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class FinanceRoutesTest extends TestCase
{
    public function test_finance_endpoints_require_authentication(): void
    {
        $this->getJson('/api/accounts')->assertStatus(Response::HTTP_UNAUTHORIZED);
        $this->getJson('/api/fiscal-years')->assertStatus(Response::HTTP_UNAUTHORIZED);
        $this->getJson('/api/fiscal-periods')->assertStatus(Response::HTTP_UNAUTHORIZED);
        $this->getJson('/api/journal-entries')->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_finance_routes_keep_expected_middleware_contract(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/accounts', 'GET'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/fiscal-years', 'GET'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/fiscal-periods', 'GET'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/journal-entries', 'GET'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/journal-entries/{journal_entry}/post', 'POST'), ['auth:api', 'resolve.tenant']);
    }

    /**
     * @param array<int, string> $expectedMiddleware
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
}
