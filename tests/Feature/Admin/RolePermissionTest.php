<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

final class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected bool $setUpPassportClient = true;

    private function adminToken(): string
    {
        $tenant = Tenant::create([
            'id'   => (string) Str::uuid(),
            'name' => 'Admin Tenant',
            'slug' => 'admin-tenant',
        ]);

        $admin = User::factory()->create([
            'email'     => 'admin@example.com',
            'password'  => Hash::make('Password123'),
            'tenant_id' => $tenant->id,
            'status'    => 'active',
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email'     => 'admin@example.com',
            'password'  => 'Password123',
            'tenant_id' => $tenant->id,
        ]);

        return (string) $loginResponse->json('data.access_token');
    }

    public function test_can_list_roles(): void
    {
        $token = $this->adminToken();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/roles');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data'])
            ->assertJsonPath('success', true);
    }

    public function test_can_create_role(): void
    {
        $token = $this->adminToken();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/admin/roles', [
                'name'       => 'custom-editor',
                'guard_name' => 'api',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'custom-editor');
    }

    public function test_can_create_permission(): void
    {
        $token = $this->adminToken();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/admin/permissions', [
                'name'       => 'manage-reports',
                'guard_name' => 'api',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'manage-reports');
    }

    public function test_can_list_permissions(): void
    {
        $token = $this->adminToken();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/permissions');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_unauthenticated_cannot_access_admin_routes(): void
    {
        $response = $this->getJson('/api/v1/admin/roles');
        $response->assertStatus(401);
    }
}
