<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);

        // Act
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user',
                'access_token',
                'refresh_token',
                'token_type',
                'expires_in',
            ]);
        
        $this->assertEquals('Bearer', $response->json('token_type'));
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        // Act
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'WrongPassword',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_fails_for_inactive_user(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
            'is_active' => false,
        ]);

        // Act
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        // Assert
        $response->assertStatus(401);
    }

    public function test_user_can_register_successfully(): void
    {
        // Act
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'newuser@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user',
                'access_token',
                'refresh_token',
            ]);
        
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'name' => 'Test User',
            'is_active' => true,
        ]);
    }

    public function test_registration_fails_with_weak_password(): void
    {
        // Act
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'newuser@example.com',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        // Arrange
        User::factory()->create(['email' => 'existing@example.com']);

        // Act
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_logout_successfully(): void
    {
        // Arrange
        $user = User::factory()->create([
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);
        
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);
        
        $token = $loginResponse->json('access_token');

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/auth/logout');

        // Assert
        $response->assertStatus(200);
    }

    public function test_user_can_refresh_token(): void
    {
        // Arrange
        $user = User::factory()->create([
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);
        
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);
        
        $refreshToken = $loginResponse->json('refresh_token');

        // Act
        $response = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $refreshToken,
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'refresh_token',
                'token_type',
                'expires_in',
            ]);
    }

    public function test_authenticated_user_can_get_profile(): void
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);
        
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);
        
        $token = $loginResponse->json('access_token');

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/auth/me');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure(['user'])
            ->assertJson([
                'user' => [
                    'email' => 'test@example.com',
                    'name' => 'Test User',
                ],
            ]);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        // Act
        $response = $this->getJson('/api/v1/auth/me');

        // Assert
        $response->assertStatus(401);
    }

    public function test_rate_limiting_on_login_attempts(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        // Act - Make 6 failed login attempts
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/v1/auth/login', [
                'email' => 'test@example.com',
                'password' => 'WrongPassword',
            ]);
        }

        // Assert - 6th attempt should be rate limited
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
        
        $this->assertStringContainsString('Too many login attempts', $response->json('errors.email.0'));
    }
}
