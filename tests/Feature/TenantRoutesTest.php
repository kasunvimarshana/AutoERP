<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class TenantRoutesTest extends TestCase
{
    public function test_tenant_endpoints_require_authentication(): void
    {
        $this->getJson('/api/tenants')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/tenant-plans')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/config/domain/example.com')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/storage/tenant-attachments/test-uuid')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_tenant_routes_keep_expected_middleware_contract(): void
    {
        $routes = app('router')->getRoutes();

        $tenantScopedRoutes = [
            ['GET', 'api/tenants'],
            ['POST', 'api/tenants'],
            ['GET', 'api/tenants/{tenant}'],
            ['PUT', 'api/tenants/{tenant}'],
            ['PATCH', 'api/tenants/{tenant}'],
            ['DELETE', 'api/tenants/{tenant}'],
            ['PATCH', 'api/tenants/{tenant}/config'],
            ['GET', 'api/tenants/{tenant}/attachments'],
            ['POST', 'api/tenants/{tenant}/attachments'],
            ['POST', 'api/tenants/{tenant}/attachments/bulk'],
            ['DELETE', 'api/tenants/{tenant}/attachments/{attachment}'],
            ['GET', 'api/tenant-plans'],
            ['GET', 'api/tenant-plans/{plan}'],
            ['POST', 'api/tenant-plans'],
            ['PUT', 'api/tenant-plans/{plan}'],
            ['PATCH', 'api/tenant-plans/{plan}'],
            ['DELETE', 'api/tenant-plans/{plan}'],
            ['GET', 'api/tenants/{tenant}/settings'],
            ['GET', 'api/tenants/{tenant}/settings/{key}'],
            ['POST', 'api/tenants/{tenant}/settings'],
            ['PUT', 'api/tenants/{tenant}/settings/{key}'],
            ['PATCH', 'api/tenants/{tenant}/settings/{key}'],
            ['DELETE', 'api/tenants/{tenant}/settings/{key}'],
        ];

        foreach ($tenantScopedRoutes as [$method, $uri]) {
            $this->assertRouteUsesMiddleware(
                $this->findRoute($routes, $uri, $method),
                ['auth.configured', 'resolve.tenant']
            );
        }

        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/config/domain/{domain}', 'GET'),
            ['auth.configured', 'throttle:60,1']
        );

        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/storage/tenant-attachments/{uuid}', 'GET'),
            ['auth.configured']
        );
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
