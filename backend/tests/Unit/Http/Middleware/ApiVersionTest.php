<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\ApiVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

/**
 * Test API Version Middleware
 */
class ApiVersionTest extends TestCase
{
    private ApiVersion $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new ApiVersion();
    }

    /** @test */
    public function it_resolves_version_from_url_path(): void
    {
        $request = Request::create('/api/v1/products', 'GET');
        
        $response = $this->middleware->handle($request, function ($req) {
            $this->assertEquals('v1', $req->attributes->get('api_version'));
            return new Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('v1', $response->headers->get('X-API-Version'));
    }

    /** @test */
    public function it_uses_default_version_when_none_specified(): void
    {
        $request = Request::create('/api/products', 'GET');
        
        $response = $this->middleware->handle($request, function ($req) {
            $this->assertEquals('v1', $req->attributes->get('api_version'));
            return new Response('OK', 200);
        });

        $this->assertEquals('v1', $response->headers->get('X-API-Version'));
    }

    /** @test */
    public function it_rejects_unsupported_api_version(): void
    {
        $request = Request::create('/api/v99/products', 'GET');
        
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(400, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Unsupported API version', $data['error']);
        $this->assertArrayHasKey('supported_versions', $data);
    }
}
