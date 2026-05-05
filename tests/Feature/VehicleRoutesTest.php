<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class VehicleRoutesTest extends TestCase
{
    private static bool $passportKeysPrepared = false;

    public function test_vehicle_named_routes_are_registered(): void
    {
        $this->assertTrue(app('router')->has('vehicles.index'));
        $this->assertTrue(app('router')->has('vehicles.store'));
        $this->assertTrue(app('router')->has('vehicles.show'));
        $this->assertTrue(app('router')->has('vehicles.destroy'));
        $this->assertTrue(app('router')->has('vehicles.status.update'));
        $this->assertTrue(app('router')->has('vehicles.dashboard'));
        $this->assertTrue(app('router')->has('vehicle-job-cards.index'));
        $this->assertTrue(app('router')->has('vehicle-job-cards.store'));
        $this->assertTrue(app('router')->has('vehicle-rentals.index'));
        $this->assertTrue(app('router')->has('vehicle-rentals.store'));
        $this->assertTrue(app('router')->has('vehicle-rentals.close'));
    }

    public function test_vehicle_endpoints_require_authentication(): void
    {
        $this->preparePassportKeys();

        $this->getJson('/api/vehicles')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/vehicles', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/vehicles/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->deleteJson('/api/vehicles/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->patchJson('/api/vehicles/1/status', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/vehicles-dashboard')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/vehicles/1/job-cards')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/vehicles/job-cards', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/vehicles/1/rentals')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/vehicles/rentals', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/vehicles/rentals/1/close', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_vehicle_routes_keep_expected_middleware_contract(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/vehicles', 'GET'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/vehicles/{vehicle}', 'GET'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/vehicles/{vehicle}/status', 'PATCH'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/vehicles-dashboard', 'GET'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/vehicles/{vehicle}/job-cards', 'GET'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/vehicles/job-cards', 'POST'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/vehicles/{vehicle}/rentals', 'GET'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/vehicles/rentals', 'POST'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/vehicles/rentals/{rental}/close', 'POST'), ['auth.configured', 'resolve.tenant']);
    }

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
