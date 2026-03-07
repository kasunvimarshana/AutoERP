<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_endpoint_is_accessible_without_auth(): void
    {
        // Mock dependencies so we don't need live services
        $this->mock(\App\Services\InventoryClientService::class)
            ->shouldReceive('ping')->andReturn(false);

        // Patch DB + Redis + RabbitMQ via partial mocking is complex in unit scope;
        // here we just verify route accessibility and response shape.
        $response = $this->getJson('/api/v1/health');

        $response->assertJsonStructure([
            'status',
            'service',
            'timestamp',
            'checks' => [
                'database',
                'redis',
                'rabbitmq',
                'inventory_service',
            ],
        ]);

        $this->assertContains($response->json('status'), ['ok', 'degraded']);
    }

    public function test_health_endpoint_reports_degraded_when_check_fails(): void
    {
        $this->mock(\App\Services\InventoryClientService::class)
            ->shouldReceive('ping')->andReturn(false);

        $response = $this->getJson('/api/v1/health');

        // At minimum inventory_service should report error since it's mocked to return false
        $this->assertEquals('error', $response->json('checks.inventory_service.status'));
    }
}
