<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a tenant first
        $response = $this->postJson('/api/v1/tenants', [
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
        ]);
        $this->tenantId = $response->json('data.id');
    }

    public function test_can_register_user(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'tenant_id' => $this->tenantId,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'john@example.com')
            ->assertJsonPath('data.tenant_id', $this->tenantId);
    }

    public function test_can_login(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'tenant_id' => $this->tenantId,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'tenant_id' => $this->tenantId,
            'email' => 'jane@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['user', 'token'],
            ]);
    }

    public function test_cannot_login_with_wrong_password(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'tenant_id' => $this->tenantId,
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'password' => 'correctpassword',
            'password_confirmation' => 'correctpassword',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'tenant_id' => $this->tenantId,
            'email' => 'bob@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_cannot_register_duplicate_email_in_same_tenant(): void
    {
        $data = [
            'tenant_id' => $this->tenantId,
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $this->postJson('/api/v1/auth/register', $data);
        $response = $this->postJson('/api/v1/auth/register', $data);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_can_get_authenticated_user(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'tenant_id' => $this->tenantId,
            'name' => 'Me User',
            'email' => 'me@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'tenant_id' => $this->tenantId,
            'email' => 'me@example.com',
            'password' => 'password123',
        ]);
        $token = $loginResponse->json('data.token');

        $response = $this->withToken($token)->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.email', 'me@example.com');
    }

    public function test_can_logout(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'tenant_id' => $this->tenantId,
            'name' => 'Logout User',
            'email' => 'logout@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'tenant_id' => $this->tenantId,
            'email' => 'logout@example.com',
            'password' => 'password123',
        ]);
        $token = $loginResponse->json('data.token');
        $tokenId = (int) explode('|', $token)[0];

        $this->withToken($token)->postJson('/api/v1/auth/logout')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Logged out successfully');

        // Confirm the token record was removed from the database.
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    }
}
