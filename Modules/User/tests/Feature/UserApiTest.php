<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\User\app\Models\User;

/**
 * User API Test
 * 
 * Tests the User API endpoints following the repository pattern
 */
class UserApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create();
    }

    /**
     * Test user can be listed
     */
    public function test_users_can_be_listed(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);
    }

    /**
     * Test user can be created
     */
    public function test_user_can_be_created(): void
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/users', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    /**
     * Test user can be retrieved
     */
    public function test_user_can_be_retrieved(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/users/{$this->user->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                ],
            ])
            ->assertJson([
                'data' => [
                    'id' => $this->user->id,
                    'email' => $this->user->email,
                ],
            ]);
    }

    /**
     * Test user can be updated
     */
    public function test_user_can_be_updated(): void
    {
        $updateData = [
            'name' => 'Updated Name',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/users/{$this->user->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'Updated Name',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Updated Name',
        ]);
    }

    /**
     * Test user can be deleted
     */
    public function test_user_can_be_deleted(): void
    {
        $userToDelete = User::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/users/{$userToDelete->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('users', [
            'id' => $userToDelete->id,
        ]);
    }

    /**
     * Test validation fails with invalid data
     */
    public function test_validation_fails_with_invalid_data(): void
    {
        $invalidData = [
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'short',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/users', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    /**
     * Test duplicate email is rejected
     */
    public function test_duplicate_email_is_rejected(): void
    {
        $userData = [
            'name' => 'Another User',
            'email' => $this->user->email, // Using existing email
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/users', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
