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

    private static bool $routesCleared = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearRoutesCacheOnce();
        $this->preparePassportKeys();
    }

    // ── BOOKINGS ───────────────────────────────────────────────────────────────

    public function test_booking_endpoints_require_authentication(): void
    {
        $this->getJson('/api/rentals/bookings')
            ->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);

        $this->postJson('/api/rentals/bookings', [])
            ->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);

        $this->getJson('/api/rentals/bookings/1')
            ->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);

        $this->putJson('/api/rentals/bookings/1', [])
            ->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);

        $this->deleteJson('/api/rentals/bookings/1')
            ->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_booking_workflow_endpoints_require_authentication(): void
    {
        $this->postJson('/api/rentals/bookings/1/activate', [])
            ->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);

        $this->postJson('/api/rentals/bookings/1/complete', [])
            ->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);

        $this->postJson('/api/rentals/bookings/1/cancel', [])
            ->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_booking_routes_have_expected_middleware(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/rentals/bookings', 'GET'),
            ['auth.configured', 'resolve.tenant'],
        );

        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/rentals/bookings', 'POST'),
            ['auth.configured', 'resolve.tenant'],
        );

        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/rentals/bookings/{id}', 'GET'),
            ['auth.configured', 'resolve.tenant'],
        );

        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/rentals/bookings/{id}', 'PUT'),
            ['auth.configured', 'resolve.tenant'],
        );

        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/rentals/bookings/{id}', 'DELETE'),
            ['auth.configured', 'resolve.tenant'],
        );

        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/rentals/bookings/{id}/activate', 'POST'),
            ['auth.configured', 'resolve.tenant'],
        );

        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/rentals/bookings/{id}/complete', 'POST'),
            ['auth.configured', 'resolve.tenant'],
        );

        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/rentals/bookings/{id}/cancel', 'POST'),
            ['auth.configured', 'resolve.tenant'],
        );
    }

    public function test_booking_named_routes_are_registered(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertNotNull($routes->getByName('rentals.bookings.index'));
        $this->assertNotNull($routes->getByName('rentals.bookings.store'));
        $this->assertNotNull($routes->getByName('rentals.bookings.show'));
        $this->assertNotNull($routes->getByName('rentals.bookings.update'));
        $this->assertNotNull($routes->getByName('rentals.bookings.destroy'));
        $this->assertNotNull($routes->getByName('rentals.bookings.activate'));
        $this->assertNotNull($routes->getByName('rentals.bookings.complete'));
        $this->assertNotNull($routes->getByName('rentals.bookings.cancel'));
    }

    // ── AVAILABILITY BRIDGE ────────────────────────────────────────────────────

    public function test_availability_bridge_endpoints_require_authentication(): void
    {
        $this->postJson('/api/rentals/availability/reserve', [])
            ->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);

        $this->postJson('/api/rentals/availability/activate', [])
            ->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);

        $this->postJson('/api/rentals/availability/release', [])
            ->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_availability_bridge_routes_have_expected_middleware(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/rentals/availability/reserve', 'POST'),
            ['auth.configured', 'resolve.tenant'],
        );

        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/rentals/availability/activate', 'POST'),
            ['auth.configured', 'resolve.tenant'],
        );

        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/rentals/availability/release', 'POST'),
            ['auth.configured', 'resolve.tenant'],
        );
    }

    public function test_availability_bridge_named_routes_are_registered(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertNotNull($routes->getByName('rentals.availability.reserve'));
        $this->assertNotNull($routes->getByName('rentals.availability.activate'));
        $this->assertNotNull($routes->getByName('rentals.availability.release'));
    }

    // ── HELPERS ────────────────────────────────────────────────────────────────

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
