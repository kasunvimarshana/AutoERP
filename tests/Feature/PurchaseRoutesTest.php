<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class PurchaseRoutesTest extends TestCase
{
    private static bool $passportKeysPrepared = false;

    public function test_purchase_order_endpoints_require_authentication(): void
    {
        $this->preparePassportKeys();

        $this->getJson('/api/purchase-orders')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/purchase-orders', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/purchase-orders/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->putJson('/api/purchase-orders/1', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->deleteJson('/api/purchase-orders/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/purchase-orders/1/confirm')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_grn_endpoints_require_authentication(): void
    {
        $this->preparePassportKeys();

        $this->getJson('/api/grns')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/grns', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/grns/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->putJson('/api/grns/1', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->deleteJson('/api/grns/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/grns/1/post')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_purchase_invoice_endpoints_require_authentication(): void
    {
        $this->preparePassportKeys();

        $this->getJson('/api/purchase-invoices')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/purchase-invoices', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/purchase-invoices/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->putJson('/api/purchase-invoices/1', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->deleteJson('/api/purchase-invoices/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/purchase-invoices/1/approve')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_purchase_return_endpoints_require_authentication(): void
    {
        $this->preparePassportKeys();

        $this->getJson('/api/purchase-returns')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/purchase-returns', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/purchase-returns/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->putJson('/api/purchase-returns/1', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->deleteJson('/api/purchase-returns/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/purchase-returns/1/post')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_purchase_routes_use_expected_middleware(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/purchase-orders', 'GET'),
            ['auth:api', 'resolve.tenant']
        );
        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/purchase-orders/{purchaseOrder}/confirm', 'POST'),
            ['auth:api', 'resolve.tenant']
        );
        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/grns', 'GET'),
            ['auth:api', 'resolve.tenant']
        );
        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/grns/{grn}/post', 'POST'),
            ['auth:api', 'resolve.tenant']
        );
        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/purchase-invoices', 'GET'),
            ['auth:api', 'resolve.tenant']
        );
        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/purchase-invoices/{invoice}/approve', 'POST'),
            ['auth:api', 'resolve.tenant']
        );
        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/purchase-returns', 'GET'),
            ['auth:api', 'resolve.tenant']
        );
        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/purchase-returns/{purchaseReturn}/post', 'POST'),
            ['auth:api', 'resolve.tenant']
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

    private function preparePassportKeys(): void
    {
        if (self::$passportKeysPrepared) {
            return;
        }

        Artisan::call('passport:keys', ['--force' => true]);

        self::$passportKeysPrepared = true;
    }
}
