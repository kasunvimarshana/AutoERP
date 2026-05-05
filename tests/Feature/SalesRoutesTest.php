<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class SalesRoutesTest extends TestCase
{
    private static bool $passportKeysPrepared = false;

    public function test_sales_order_endpoints_require_authentication(): void
    {
        $this->preparePassportKeys();

        $this->getJson('/api/sales-orders')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/sales-orders', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/sales-orders/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->putJson('/api/sales-orders/1', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->deleteJson('/api/sales-orders/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/sales-orders/1/confirm')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/sales-orders/1/cancel')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_shipment_endpoints_require_authentication(): void
    {
        $this->preparePassportKeys();

        $this->getJson('/api/shipments')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/shipments', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/shipments/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->putJson('/api/shipments/1', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->deleteJson('/api/shipments/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/shipments/1/process')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_sales_invoice_endpoints_require_authentication(): void
    {
        $this->preparePassportKeys();

        $this->getJson('/api/sales-invoices')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/sales-invoices', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/sales-invoices/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->putJson('/api/sales-invoices/1', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->deleteJson('/api/sales-invoices/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/sales-invoices/1/post')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/sales-invoices/1/record-payment', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_sales_return_endpoints_require_authentication(): void
    {
        $this->preparePassportKeys();

        $this->getJson('/api/sales-returns')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/sales-returns', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/sales-returns/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->putJson('/api/sales-returns/1', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->deleteJson('/api/sales-returns/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/sales-returns/1/approve')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/sales-returns/1/receive')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_sales_routes_use_expected_middleware(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/sales-orders', 'GET'),
            ['auth.configured', 'resolve.tenant']
        );
        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/sales-orders/{salesOrder}/confirm', 'POST'),
            ['auth.configured', 'resolve.tenant']
        );
        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/sales-orders/{salesOrder}/cancel', 'POST'),
            ['auth.configured', 'resolve.tenant']
        );
        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/shipments', 'GET'),
            ['auth.configured', 'resolve.tenant']
        );
        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/shipments/{shipment}/process', 'POST'),
            ['auth.configured', 'resolve.tenant']
        );
        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/sales-invoices', 'GET'),
            ['auth.configured', 'resolve.tenant']
        );
        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/sales-invoices/{salesInvoice}/post', 'POST'),
            ['auth.configured', 'resolve.tenant']
        );
        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/sales-invoices/{salesInvoice}/record-payment', 'POST'),
            ['auth.configured', 'resolve.tenant']
        );
        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/sales-returns', 'GET'),
            ['auth.configured', 'resolve.tenant']
        );
        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/sales-returns/{salesReturn}/approve', 'POST'),
            ['auth.configured', 'resolve.tenant']
        );
        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/sales-returns/{salesReturn}/receive', 'POST'),
            ['auth.configured', 'resolve.tenant']
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
