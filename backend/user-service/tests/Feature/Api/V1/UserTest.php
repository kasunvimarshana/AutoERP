<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Services\PassportTokenValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private int $tenantId = 1;

    protected function setUp(): void
    {
        parent::setUp();

        app()->instance('current_tenant_id', $this->tenantId);

        $this->adminUser = User::create([
            'tenant_id' => $this->tenantId,
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'roles' => ['admin'],
            'attributes' => ['department' => 'IT', 'region' => 'US'],
            'is_active' => true,
        ]);

        // Mock token validation
        $mock = Mockery::mock(PassportTokenValidationService::class);
        $mock->shouldReceive('validate')->andReturn([
            'active' => true,
            'user_id' => $this->adminUser->id,
            'tenant_id' => $this->tenantId,
            'email' => $this->adminUser->email,
            'name' => $this->adminUser->name,
            'roles' => ['admin'],
            'attributes' => [],
        ]);
        app()->instance(PassportTokenValidationService::class, $mock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_health_endpoint(): void
    {
        $this->getJson('/api/health')
            ->assertStatus(200)
            ->assertJson(['status' => 'ok', 'service' => 'user-service']);
    }

    public function test_list_users_requires_auth(): void
    {
        $this->getJson('/api/v1/users')->assertStatus(401);
    }

    public function test_admin_can_list_users(): void
    {
        $this->withToken('fake-token')
            ->getJson('/api/v1/users')
            ->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_list_users_with_pagination(): void
    {
        $this->withToken('fake-token')
            ->getJson('/api/v1/users?per_page=10')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta', 'links']);
    }

    public function test_list_users_without_per_page_returns_all(): void
    {
        User::create([
            'tenant_id' => $this->tenantId,
            'name' => 'Second User',
            'email' => 'second@test.com',
            'password' => bcrypt('password'),
            'roles' => ['staff'],
            'is_active' => true,
        ]);

        $response = $this->withToken('fake-token')
            ->getJson('/api/v1/users')
            ->assertStatus(200)
            ->assertJsonStructure(['data']);

        $this->assertArrayNotHasKey('meta', $response->json());
        $this->assertArrayNotHasKey('links', $response->json());
        $this->assertCount(2, $response->json('data'));
    }

    public function test_admin_can_create_user(): void
    {
        $this->withToken('fake-token')
            ->postJson('/api/v1/users', [
                'name' => 'New Staff',
                'email' => 'staff@test.com',
                'password' => 'password123',
                'roles' => ['staff'],
            ])
            ->assertStatus(201)
            ->assertJsonStructure(['message', 'data' => ['id', 'email', 'roles']]);
    }

    public function test_can_update_user_roles(): void
    {
        $user = User::create([
            'tenant_id' => $this->tenantId,
            'name' => 'Staff User',
            'email' => 'staff2@test.com',
            'password' => bcrypt('password'),
            'roles' => ['staff'],
            'is_active' => true,
        ]);

        $this->withToken('fake-token')
            ->patchJson("/api/v1/users/{$user->id}/roles", ['roles' => ['manager']])
            ->assertStatus(200);
    }

    public function test_get_roles_list(): void
    {
        $this->withToken('fake-token')
            ->getJson('/api/v1/roles')
            ->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_list_roles_with_pagination(): void
    {
        $this->withToken('fake-token')
            ->getJson('/api/v1/roles?per_page=2')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta', 'links']);
    }

    public function test_list_roles_without_per_page_returns_all(): void
    {
        $response = $this->withToken('fake-token')
            ->getJson('/api/v1/roles')
            ->assertStatus(200)
            ->assertJsonStructure(['data']);

        $this->assertArrayNotHasKey('meta', $response->json());
        $this->assertArrayNotHasKey('links', $response->json());
        $this->assertCount(3, $response->json('data'));
    }

    public function test_list_permissions_with_pagination(): void
    {
        $this->withToken('fake-token')
            ->getJson('/api/v1/permissions?per_page=3')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta', 'links']);
    }

    public function test_list_permissions_without_per_page_returns_all(): void
    {
        $response = $this->withToken('fake-token')
            ->getJson('/api/v1/permissions')
            ->assertStatus(200)
            ->assertJsonStructure(['data']);

        $this->assertArrayNotHasKey('meta', $response->json());
        $this->assertArrayNotHasKey('links', $response->json());
        $this->assertCount(8, $response->json('data'));
    }
}
