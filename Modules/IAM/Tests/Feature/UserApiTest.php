<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $authUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an authenticated user for testing
        $this->authUser = User::factory()->create([
            'is_active' => true,
        ]);
    }

    public function test_it_lists_users(): void
    {
        // Arrange
        User::factory()->count(5)->create();

        // Act
        $response = $this->actingAs($this->authUser, 'sanctum')
            ->getJson('/api/v1/iam/users');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'is_active',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);
    }

    public function test_it_creates_user_with_valid_data(): void
    {
        // Arrange
        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_active' => true,
        ];

        // Act
        $response = $this->actingAs($this->authUser, 'sanctum')
            ->postJson('/api/v1/iam/users', $userData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'name',
                'email',
                'is_active',
            ])
            ->assertJson([
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'is_active' => true,
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'name' => 'New User',
        ]);
    }

    public function test_it_validates_required_fields_on_create(): void
    {
        // Act
        $response = $this->actingAs($this->authUser, 'sanctum')
            ->postJson('/api/v1/iam/users', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_it_validates_unique_email_on_create(): void
    {
        // Arrange
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);

        // Act
        $response = $this->actingAs($this->authUser, 'sanctum')
            ->postJson('/api/v1/iam/users', [
                'name' => 'Test User',
                'email' => 'existing@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_it_shows_user_details(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($this->authUser, 'sanctum')
            ->getJson("/api/v1/iam/users/{$user->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);
    }

    public function test_it_updates_user_successfully(): void
    {
        // Arrange
        $user = User::factory()->create(['name' => 'Old Name']);

        // Act
        $response = $this->actingAs($this->authUser, 'sanctum')
            ->putJson("/api/v1/iam/users/{$user->id}", [
                'name' => 'Updated Name',
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'name' => 'Updated Name',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_it_deletes_user_successfully(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($this->authUser, 'sanctum')
            ->deleteJson("/api/v1/iam/users/{$user->id}");

        // Assert
        $response->assertStatus(204);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_it_activates_user(): void
    {
        // Arrange
        $user = User::factory()->create(['is_active' => false]);

        // Act
        $response = $this->actingAs($this->authUser, 'sanctum')
            ->postJson("/api/v1/iam/users/{$user->id}/activate");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'is_active' => true,
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => true,
        ]);
    }

    public function test_it_deactivates_user(): void
    {
        // Arrange
        $user = User::factory()->create(['is_active' => true]);

        // Act
        $response = $this->actingAs($this->authUser, 'sanctum')
            ->postJson("/api/v1/iam/users/{$user->id}/deactivate");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'is_active' => false,
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => false,
        ]);
    }

    public function test_it_searches_users(): void
    {
        // Arrange
        User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

        // Act
        $response = $this->actingAs($this->authUser, 'sanctum')
            ->getJson('/api/v1/iam/users/search?query=John');

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertGreaterThan(0, count($data));
    }

    public function test_it_requires_authentication(): void
    {
        // Act
        $response = $this->getJson('/api/v1/iam/users');

        // Assert
        $response->assertStatus(401);
    }
}
