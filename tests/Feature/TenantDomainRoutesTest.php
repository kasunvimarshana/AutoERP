<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class TenantDomainRoutesTest extends TestCase
{
    public function test_tenant_domain_endpoints_require_authentication(): void
    {
        $this->getJson('/api/tenants/1/domains')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/tenants/1/domains/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_tenant_domain_routes_keep_expected_middleware_contract(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/tenants/{tenant}/domains', 'GET'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/tenants/{tenant}/domains/{domain}', 'GET'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/tenants/{tenant}/domains', 'POST'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/tenants/{tenant}/domains/{domain}', 'PUT'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/tenants/{tenant}/domains/{domain}', 'PATCH'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/tenants/{tenant}/domains/{domain}', 'DELETE'), ['auth.configured', 'resolve.tenant']);
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
}
