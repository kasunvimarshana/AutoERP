<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class RentalRoutesTest extends TestCase
{
    private static bool $passportKeysPrepared = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->preparePassportKeys();
    }

    public function test_rental_reservation_endpoints_require_authentication(): void
    {
        $this->postJson('/api/v1/rentals/reservations', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/rentals/reservations', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/v1/rentals/reservations')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/v1/rentals/reservations/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->putJson('/api/v1/rentals/reservations/1', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/v1/rentals/reservations/1/confirm', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/v1/rentals/reservations/1/cancel', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_rental_agreement_endpoints_require_authentication(): void
    {
        $this->postJson('/api/v1/rentals/agreements', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/v1/rentals/agreements/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/v1/rentals/agreements/active')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_rental_transaction_endpoints_require_authentication(): void
    {
        $this->postJson('/api/v1/rentals/transactions/checkout', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/v1/rentals/transactions/checkin', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/v1/rentals/transactions/open')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_rental_routes_keep_expected_middleware_contract(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/v1/rentals/reservations', 'GET'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/rentals/reservations', 'GET'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/v1/rentals/agreements/{id}', 'GET'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/v1/rentals/transactions/open', 'GET'), ['auth.configured', 'resolve.tenant']);
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
