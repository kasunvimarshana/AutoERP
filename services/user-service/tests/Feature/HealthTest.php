<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/v1/health');

        // We expect either 200 (healthy) or 503 (degraded) — both are valid JSON responses
        $this->assertContains($response->status(), [200, 503]);

        $response->assertJsonStructure([
            'service',
            'status',
            'timestamp',
            'checks' => [
                'database' => ['status'],
                'redis'    => ['status'],
                'rabbitmq' => ['status'],
            ],
        ]);
    }

    public function test_health_response_includes_service_name(): void
    {
        $response = $this->getJson('/api/v1/health');

        $this->assertContains($response->status(), [200, 503]);
        $this->assertEquals('user-service', $response->json('service'));
    }

    public function test_health_database_check_present(): void
    {
        $response = $this->getJson('/api/v1/health');

        $this->assertContains($response->status(), [200, 503]);
        $this->assertArrayHasKey('status', $response->json('checks.database'));
    }
}
