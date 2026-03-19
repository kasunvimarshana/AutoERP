<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PublicKeyTest extends TestCase
{
    use RefreshDatabase;

    protected bool $setUpPassportClient = true;

    public function test_public_key_endpoint_returns_pem_key(): void
    {
        $response = $this->getJson('/api/v1/auth/public-key');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'public_key',
                    'algorithm',
                    'fingerprint',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.algorithm', 'RS256');

        $this->assertStringContainsString(
            'PUBLIC KEY',
            $response->json('data.public_key')
        );
    }
}
