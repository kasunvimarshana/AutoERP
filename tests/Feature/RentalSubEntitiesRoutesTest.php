<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class RentalSubEntitiesRoutesTest extends TestCase
{
    private static bool $passportKeysPrepared = false;

    private static bool $routesCleared = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearRoutesCacheOnce();
        $this->preparePassportKeys();
    }

    // ── DRIVER ASSIGNMENTS ─────────────────────────────────────────────────────

    public function test_driver_assignment_endpoints_require_authentication(): void
    {
        $this->getJson('/api/rentals/bookings/1/driver-assignments')
            ->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);

        $this->postJson('/api/rentals/bookings/1/driver-assignments', [])
            ->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);

        $this->postJson('/api/rentals/bookings/1/driver-assignments/1/substitute', [])
            ->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);

        $this->deleteJson('/api/rentals/bookings/1/driver-assignments/1')
            ->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_driver_assignment_routes_have_expected_middleware(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/rentals/bookings/{bookingId}/driver-assignments', 'GET'),
            ['auth.configured', 'resolve.tenant'],
        );

        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/rentals/bookings/{bookingId}/driver-assignments', 'POST'),
            ['auth.configured', 'resolve.tenant'],
        );

        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/rentals/bookings/{bookingId}/driver-assignments/{id}/substitute', 'POST'),
            ['auth.configured', 'resolve.tenant'],
        );

        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/rentals/bookings/{bookingId}/driver-assignments/{id}', 'DELETE'),
            ['auth.configured', 'resolve.tenant'],
        );
    }

    public function test_driver_assignment_named_routes_are_registered(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertNotNull($routes->getByName('rentals.driver-assignments.index'));
        $this->assertNotNull($routes->getByName('rentals.driver-assignments.store'));
        $this->assertNotNull($routes->getByName('rentals.driver-assignments.show'));
        $this->assertNotNull($routes->getByName('rentals.driver-assignments.substitute'));
        $this->assertNotNull($routes->getByName('rentals.driver-assignments.destroy'));
    }

    // ── INCIDENTS ──────────────────────────────────────────────────────────────

    public function test_incident_endpoints_require_authentication(): void
    {
        $this->getJson('/api/rentals/incidents')
            ->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);

        $this->postJson('/api/rentals/incidents', [])
            ->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);

        $this->putJson('/api/rentals/incidents/1', [])
            ->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);

        $this->deleteJson('/api/rentals/incidents/1')
            ->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_incident_routes_have_expected_middleware(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/rentals/incidents', 'GET'),
            ['auth.configured', 'resolve.tenant'],
        );

        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/rentals/incidents', 'POST'),
            ['auth.configured', 'resolve.tenant'],
        );

        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/rentals/incidents/{id}', 'PUT'),
            ['auth.configured', 'resolve.tenant'],
        );

        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/rentals/incidents/{id}', 'DELETE'),
            ['auth.configured', 'resolve.tenant'],
        );
    }

    public function test_incident_named_routes_are_registered(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertNotNull($routes->getByName('rentals.incidents.index'));
        $this->assertNotNull($routes->getByName('rentals.incidents.store'));
        $this->assertNotNull($routes->getByName('rentals.incidents.show'));
        $this->assertNotNull($routes->getByName('rentals.incidents.update'));
        $this->assertNotNull($routes->getByName('rentals.incidents.destroy'));
    }

    // ── DEPOSITS ───────────────────────────────────────────────────────────────

    public function test_deposit_endpoints_require_authentication(): void
    {
        $this->getJson('/api/rentals/bookings/1/deposits')
            ->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);

        $this->postJson('/api/rentals/bookings/1/deposits', [])
            ->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);

        $this->postJson('/api/rentals/bookings/1/deposits/1/release', [])
            ->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);

        $this->deleteJson('/api/rentals/bookings/1/deposits/1')
            ->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_deposit_routes_have_expected_middleware(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/rentals/bookings/{bookingId}/deposits', 'GET'),
            ['auth.configured', 'resolve.tenant'],
        );

        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/rentals/bookings/{bookingId}/deposits', 'POST'),
            ['auth.configured', 'resolve.tenant'],
        );

        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/rentals/bookings/{bookingId}/deposits/{id}/release', 'POST'),
            ['auth.configured', 'resolve.tenant'],
        );

        $this->assertRouteUsesMiddleware(
            $this->findRoute($routes, 'api/rentals/bookings/{bookingId}/deposits/{id}', 'DELETE'),
            ['auth.configured', 'resolve.tenant'],
        );
    }

    public function test_deposit_named_routes_are_registered(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertNotNull($routes->getByName('rentals.deposits.index'));
        $this->assertNotNull($routes->getByName('rentals.deposits.store'));
        $this->assertNotNull($routes->getByName('rentals.deposits.show'));
        $this->assertNotNull($routes->getByName('rentals.deposits.release'));
        $this->assertNotNull($routes->getByName('rentals.deposits.destroy'));
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
