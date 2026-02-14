<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiHealthCheckTest extends TestCase
{
    public function test_api_health_endpoint_returns_success()
    {
        $response = $this->get('/api/health');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'healthy'
        ]);
    }

    public function test_web_health_endpoint_returns_success()
    {
        $response = $this->get('/health');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'healthy'
        ]);
    }
}
