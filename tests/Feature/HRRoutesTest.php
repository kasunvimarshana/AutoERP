<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class HRRoutesTest extends TestCase
{
    private static bool $passportKeysPrepared = false;

    private static bool $routesCleared = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearRoutesCacheOnce();
        $this->preparePassportKeys();
    }

    // -------------------------------------------------------------------------
    // Unauthenticated access
    // -------------------------------------------------------------------------

    public function test_hr_endpoints_require_authentication(): void
    {
        $this->getJson('/api/hr/shifts')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/hr/shifts/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/hr/leave-types')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/hr/leave-policies')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/hr/leave-balances')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/hr/leave-requests')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/hr/attendance-logs')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/hr/attendance-records')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/hr/biometric-devices')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/hr/payroll-runs')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/hr/payroll-items')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/hr/payslips')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/hr/performance-cycles')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/hr/performance-reviews')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/hr/employee-documents')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    // -------------------------------------------------------------------------
    // Middleware contract
    // -------------------------------------------------------------------------

    public function test_shift_routes_have_expected_middleware(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertRouteMiddleware($this->findRoute($routes, 'api/hr/shifts', 'GET'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteMiddleware($this->findRoute($routes, 'api/hr/shifts/{shift}', 'GET'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteMiddleware($this->findRoute($routes, 'api/hr/shifts', 'POST'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteMiddleware($this->findRoute($routes, 'api/hr/shifts/{shift}', 'PUT'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteMiddleware($this->findRoute($routes, 'api/hr/shifts/{shift}', 'DELETE'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteMiddleware($this->findRoute($routes, 'api/hr/shifts/{shift}/assign', 'POST'), ['auth:api', 'resolve.tenant']);
    }

    public function test_leave_type_routes_have_expected_middleware(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertRouteMiddleware($this->findRoute($routes, 'api/hr/leave-types', 'GET'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteMiddleware($this->findRoute($routes, 'api/hr/leave-types/{leave_type}', 'GET'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteMiddleware($this->findRoute($routes, 'api/hr/leave-types', 'POST'), ['auth:api', 'resolve.tenant']);
    }

    public function test_leave_request_routes_have_expected_middleware(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertRouteMiddleware($this->findRoute($routes, 'api/hr/leave-requests', 'GET'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteMiddleware($this->findRoute($routes, 'api/hr/leave-requests', 'POST'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteMiddleware($this->findRoute($routes, 'api/hr/leave-requests/{leave_request}/approve', 'POST'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteMiddleware($this->findRoute($routes, 'api/hr/leave-requests/{leave_request}/reject', 'POST'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteMiddleware($this->findRoute($routes, 'api/hr/leave-requests/{leave_request}/cancel', 'POST'), ['auth:api', 'resolve.tenant']);
    }

    public function test_payroll_run_routes_have_expected_middleware(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertRouteMiddleware($this->findRoute($routes, 'api/hr/payroll-runs', 'GET'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteMiddleware($this->findRoute($routes, 'api/hr/payroll-runs', 'POST'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteMiddleware($this->findRoute($routes, 'api/hr/payroll-runs/{payroll_run}/approve', 'POST'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteMiddleware($this->findRoute($routes, 'api/hr/payroll-runs/{payroll_run}/process', 'POST'), ['auth:api', 'resolve.tenant']);
    }

    public function test_attendance_routes_have_expected_middleware(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertRouteMiddleware($this->findRoute($routes, 'api/hr/attendance-logs', 'GET'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteMiddleware($this->findRoute($routes, 'api/hr/attendance-logs', 'POST'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteMiddleware($this->findRoute($routes, 'api/hr/attendance-records', 'GET'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteMiddleware($this->findRoute($routes, 'api/hr/attendance-records/process', 'POST'), ['auth:api', 'resolve.tenant']);
    }

    public function test_biometric_device_routes_have_expected_middleware(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertRouteMiddleware($this->findRoute($routes, 'api/hr/biometric-devices', 'GET'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteMiddleware($this->findRoute($routes, 'api/hr/biometric-devices/{biometric_device}/sync', 'POST'), ['auth:api', 'resolve.tenant']);
    }

    public function test_performance_routes_have_expected_middleware(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertRouteMiddleware($this->findRoute($routes, 'api/hr/performance-cycles', 'GET'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteMiddleware($this->findRoute($routes, 'api/hr/performance-reviews', 'GET'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteMiddleware($this->findRoute($routes, 'api/hr/performance-reviews/{performance_review}/submit', 'POST'), ['auth:api', 'resolve.tenant']);
    }

    public function test_employee_document_routes_have_expected_middleware(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertRouteMiddleware($this->findRoute($routes, 'api/hr/employee-documents', 'GET'), ['auth:api', 'resolve.tenant']);
        $this->assertRouteMiddleware($this->findRoute($routes, 'api/hr/employee-documents', 'POST'), ['auth:api', 'resolve.tenant']);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** @param array<int, string> $expectedMiddleware */
    private function assertRouteMiddleware(Route $route, array $expectedMiddleware): void
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
