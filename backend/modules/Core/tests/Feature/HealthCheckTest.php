<?php

namespace Modules\Core\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_healthy_status()
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'timestamp',
                'checks' => [
                    'database' => ['status', 'message'],
                    'cache' => ['status', 'message'],
                ],
            ])
            ->assertJson([
                'status' => 'healthy',
            ]);
    }

    public function test_health_check_validates_database_connection()
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJson([
                'checks' => [
                    'database' => [
                        'status' => 'ok',
                    ],
                ],
            ]);
    }

    public function test_health_check_validates_cache()
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJson([
                'checks' => [
                    'cache' => [
                        'status' => 'ok',
                    ],
                ],
            ]);
    }
}
