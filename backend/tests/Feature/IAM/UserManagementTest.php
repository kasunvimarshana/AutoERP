<?php

declare(strict_types=1);

namespace Tests\Feature\IAM;

use App\Models\User;
use App\Models\Tenant;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create tenant
        $this->tenant = Tenant::factory()->create();
        
        // Create admin role and user
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $this->adminUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->adminUser->assignRole($adminRole);
    }

    public function test_can_list_users(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/iam/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data',
                    'current_page',
                    'per_page',
                ],
            ]);
    }

    public function test_can_create_user(): void
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'tenant_id' => $this->tenant->id,
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/iam/users', $userData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'User created successfully',
            ])
            ->assertJsonPath('data.email', 'test@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_can_show_user(): void
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/iam/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_can_update_user(): void
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $updateData = [
            'name' => 'Updated Name',
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson("/api/iam/users/{$user->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User updated successfully',
            ])
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_can_delete_user(): void
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson("/api/iam/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User deleted successfully',
            ]);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_can_assign_roles_to_user(): void
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        
        $role = Role::create(['name' => 'manager', 'guard_name' => 'web']);

        $response = $this->actingAs($this->adminUser)
            ->postJson("/api/iam/users/{$user->id}/roles", [
                'roles' => ['manager'],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Roles assigned successfully',
            ]);

        $this->assertTrue($user->fresh()->hasRole('manager'));
    }

    public function test_validation_fails_for_invalid_user_data(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/iam/users', [
                'name' => '',
                'email' => 'invalid-email',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_cannot_create_user_with_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'duplicate@example.com',
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/iam/users', [
                'name' => 'Test User',
                'email' => 'duplicate@example.com',
                'password' => 'password123',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
