<?php

namespace Tests\Feature\Api;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\FeatureTestCase;

/**
 * Authentication Test
 *
 * Tests the authentication endpoints.
 */
class AuthenticationTest extends FeatureTestCase
{
    use RefreshDatabase;

    private string $baseUri = '/api/v1/auth';

    /** @test */
    public function users_can_register()
    {
        $tenant = Tenant::factory()->create();

        $userData = [
            'tenant_slug' => $tenant->slug,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson("{$this->baseUri}/register", $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                    ],
                    'token',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'name' => 'John Doe',
                        'email' => 'john@example.com',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'tenant_id' => $tenant->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function registration_requires_valid_data()
    {
        $response = $this->postJson("{$this->baseUri}/register", []);

        $this->assertValidationErrors($response, [
            'tenant_slug',
            'name',
            'email',
            'password',
        ]);
    }

    /** @test */
    public function registration_requires_unique_email()
    {
        $tenant = Tenant::factory()->create();

        User::factory()->forTenant($tenant)->create([
            'email' => 'existing@example.com',
        ]);

        $response = $this->postJson("{$this->baseUri}/register", [
            'tenant_slug' => $tenant->slug,
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertValidationErrors($response, ['email']);
    }

    /** @test */
    public function users_can_login_with_valid_credentials()
    {
        $user = User::factory()->forTenant($this->tenant)->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson("{$this->baseUri}/login", [
            'tenant_slug' => $this->tenant->slug,
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                    ],
                    'token',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'email' => 'user@example.com',
                    ],
                ],
            ]);
    }

    /** @test */
    public function users_cannot_login_with_invalid_credentials()
    {
        User::factory()->forTenant($this->tenant)->create([
            'email' => 'user@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->postJson("{$this->baseUri}/login", [
            'tenant_slug' => $this->tenant->slug,
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials',
            ]);
    }

    /** @test */
    public function inactive_users_cannot_login()
    {
        $user = User::factory()->forTenant($this->tenant)->inactive()->create([
            'email' => 'inactive@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson("{$this->baseUri}/login", [
            'tenant_slug' => $this->tenant->slug,
            'email' => 'inactive@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Account is inactive',
            ]);
    }

    /** @test */
    public function authenticated_users_can_get_their_profile()
    {
        $user = $this->actingAsUser();

        $response = $this->getJson("{$this->baseUri}/me");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
    }

    /** @test */
    public function unauthenticated_users_cannot_get_profile()
    {
        $response = $this->getJson("{$this->baseUri}/me");

        $response->assertStatus(401);
    }

    /** @test */
    public function authenticated_users_can_logout()
    {
        $user = User::factory()->forTenant($this->tenant)->create();

        // Create a token for the user
        $token = $user->createToken('auth-token')->plainTextToken;

        // Logout
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("{$this->baseUri}/logout");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);

        // Verify token is revoked
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
        ]);
    }

    /** @test */
    public function users_can_update_their_profile()
    {
        $user = $this->actingAsUser();

        $response = $this->putJson("{$this->baseUri}/profile", [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Updated Name',
                    'email' => 'updated@example.com',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    /** @test */
    public function users_can_change_their_password()
    {
        $user = $this->actingAsUser([
            'password' => Hash::make('old-password'),
        ]);

        $response = $this->putJson("{$this->baseUri}/change-password", [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Password changed successfully',
            ]);

        $user->refresh();
        $this->assertTrue(Hash::check('new-password', $user->password));
    }

    /** @test */
    public function password_change_requires_correct_current_password()
    {
        $user = $this->actingAsUser([
            'password' => Hash::make('old-password'),
        ]);

        $response = $this->putJson("{$this->baseUri}/change-password", [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Current password is incorrect',
            ]);

        $user->refresh();
        $this->assertTrue(Hash::check('old-password', $user->password));
    }

    /** @test */
    public function api_returns_tenant_info_with_authenticated_requests()
    {
        $user = $this->actingAsUser();

        $response = $this->getJson("{$this->baseUri}/me");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'tenant' => [
                        'id',
                        'name',
                        'slug',
                    ],
                ],
            ]);
    }
}
