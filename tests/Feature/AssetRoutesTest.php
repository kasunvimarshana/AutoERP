<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class AssetRoutesTest extends TestCase
{
    private static bool $passportKeysPrepared = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->preparePassportKeys();
    }

    public function test_asset_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/assets')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/assets')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/v1/assets', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/v1/assets/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->putJson('/api/v1/assets/1', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->deleteJson('/api/v1/assets/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_vehicle_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/assets/vehicles')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/v1/assets/vehicles', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/v1/assets/vehicles/available')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/v1/assets/vehicles/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->putJson('/api/v1/assets/vehicles/1', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->deleteJson('/api/v1/assets/vehicles/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_asset_owner_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/assets/owners')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/v1/assets/owners', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/v1/assets/owners/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->putJson('/api/v1/assets/owners/1', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->deleteJson('/api/v1/assets/owners/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_asset_document_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/assets/documents')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/v1/assets/documents', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/v1/assets/documents/expiring')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/v1/assets/documents/1')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_depreciation_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/assets/depreciation')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->getJson('/api/v1/assets/depreciation/pending')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
        $this->postJson('/api/v1/assets/depreciation/1/post', [])->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_asset_routes_keep_expected_middleware_contract(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/v1/assets', 'GET'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/assets', 'GET'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/v1/assets/owners', 'GET'), ['auth.configured', 'resolve.tenant']);
        $this->assertRouteUsesMiddleware($this->findRoute($routes, 'api/v1/assets/vehicles', 'GET'), ['auth.configured', 'resolve.tenant']);
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
