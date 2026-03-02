<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Verify the API health endpoint is reachable and returns the expected structure.
     */
    public function test_the_api_health_endpoint_returns_a_successful_response(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'API is healthy')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['service', 'version', 'timestamp'],
                'errors',
            ]);
    }
}
