<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_json_structure(): void
    {
        $response = $this->getJson('/api/v1/health');

        // May be 200 (healthy) or 503 (degraded), both are valid responses
        $this->assertContains($response->status(), [200, 503]);

        $response->assertJsonStructure([
            'service',
            'status',
            'checks' => [
                'database',
                'redis',
                'rabbitmq',
                'product_service',
            ],
            'timestamp',
        ]);
    }

    public function test_health_endpoint_includes_service_name(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertJsonPath('service', 'inventory-service');
    }

    public function test_health_database_check_passes_in_test_environment(): void
    {
        $response = $this->getJson('/api/v1/health');

        $checks = $response->json('checks');

        // Database should always be ok in test environment
        $this->assertEquals('ok', $checks['database']['status']);
    }

    public function test_health_endpoint_is_accessible_without_auth(): void
    {
        // No auth headers — should still respond (health is public)
        $response = $this->getJson('/api/v1/health');

        $this->assertNotEquals(401, $response->status());
        $this->assertNotEquals(403, $response->status());
    }
}
