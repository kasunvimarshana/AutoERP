<?php

namespace Modules\IAM\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Role;
use Modules\IAM\Models\User;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
        $this->seed(\Modules\IAM\Database\Seeders\IAMSeeder::class);

        $this->adminUser = User::factory()->create(['is_active' => true]);
        $this->adminUser->assignRole('admin');

        $this->regularUser = User::factory()->create(['is_active' => true]);
        $this->regularUser->assignRole('user');
    }

    public function test_admin_can_view_users(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/users');

        $response->assertStatus(200);
    }

    public function test_regular_user_can_view_users(): void
    {
        Sanctum::actingAs($this->regularUser);

        $response = $this->getJson('/api/users');

        $response->assertStatus(200);
    }

    public function test_admin_can_create_user(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->postJson('/api/users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
        ]);

        $response->assertStatus(201);
    }

    public function test_regular_user_cannot_create_user(): void
    {
        Sanctum::actingAs($this->regularUser);

        $response = $this->postJson('/api/users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_without_permission_cannot_access_protected_route(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/roles');

        $response->assertStatus(403);
    }

    public function test_permission_checking_works_with_role_hierarchy(): void
    {
        $parentRole = Role::create([
            'name' => 'parent-role',
            'guard_name' => 'web',
        ]);

        $childRole = Role::create([
            'name' => 'child-role',
            'guard_name' => 'web',
            'parent_id' => $parentRole->id,
        ]);

        $permission = Permission::create([
            'name' => 'test.permission',
            'guard_name' => 'web',
            'resource' => 'test',
            'action' => 'permission',
        ]);

        $parentRole->givePermissionTo($permission);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($childRole);

        $this->assertTrue($childRole->getAllPermissions()->contains($permission));
    }

    public function test_multi_tenant_user_isolation(): void
    {
        $tenant1User = User::factory()->create([
            'tenant_id' => 1,
            'is_active' => true,
        ]);
        $tenant1User->assignRole('admin');

        $tenant2User = User::factory()->create([
            'tenant_id' => 2,
            'is_active' => true,
        ]);

        Sanctum::actingAs($tenant1User);

        $response = $this->getJson('/api/users/'.$tenant2User->id);

        $response->assertStatus(404);
    }
}
