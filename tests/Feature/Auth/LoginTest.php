<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domain\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected bool $setUpPassportClient = true;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email'    => 'test@example.com',
            'password' => Hash::make('Password123'),
            'status'   => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'test@example.com',
            'password' => 'Password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'access_token',
                    'token_type',
                    'expires_in',
                ],
            ])
            ->assertJson(['success' => true]);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        User::factory()->create([
            'email'    => 'test@example.com',
            'password' => Hash::make('Password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'test@example.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    public function test_login_fails_with_inactive_account(): void
    {
        User::factory()->create([
            'email'    => 'inactive@example.com',
            'password' => Hash::make('Password123'),
            'status'   => 'inactive',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'inactive@example.com',
            'password' => 'Password123',
        ]);

        $response->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_login_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_authenticated_user_can_access_me_endpoint(): void
    {
        $user = User::factory()->create([
            'email'    => 'me@example.com',
            'password' => Hash::make('Password123'),
            'status'   => 'active',
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email'    => 'me@example.com',
            'password' => 'Password123',
        ]);

        $token = $loginResponse->json('data.access_token');

        $meResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');

        $meResponse->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.email', 'me@example.com');
    }

    public function test_health_check_returns_ok(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'ok');
    }
}
