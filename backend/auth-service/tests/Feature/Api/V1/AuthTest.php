<?php

namespace Tests\Feature\Api\V1;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'is_active' => true,
            'plan' => 'basic',
        ]);

        $this->adminUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'roles' => ['admin'],
            'is_active' => true,
        ]);
    }

    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/health');
        $response->assertStatus(200)
            ->assertJson(['status' => 'ok', 'service' => 'auth-service']);
    }

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'tenant_slug' => 'test-tenant',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email', 'roles'],
                'token' => ['access_token', 'token_type'],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        app()->instance('current_tenant_id', $this->tenant->id);

        User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Existing User',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
            'roles' => ['staff'],
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'tenant_slug' => 'test-tenant',
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_login(): void
    {
        app()->instance('current_tenant_id', $this->tenant->id);

        User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => Hash::make('password123'),
            'roles' => ['staff'],
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'password123',
            'tenant_id' => $this->tenant->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'email'],
                'token' => ['access_token', 'token_type', 'tenant_id'],
            ]);
    }

    public function test_login_fails_with_wrong_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'notexist@example.com',
            'password' => 'wrongpass',
            'tenant_id' => $this->tenant->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_me_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/auth/me');
        $response->assertStatus(401);
    }

    public function test_list_tenants_requires_auth(): void
    {
        $this->getJson('/api/v1/tenants')->assertStatus(401);
    }

    public function test_list_tenants_with_pagination(): void
    {
        $this->actingAs($this->adminUser, 'api')
            ->getJson('/api/v1/tenants?per_page=10')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta', 'links']);
    }

    public function test_list_tenants_without_per_page_returns_all(): void
    {
        Tenant::create([
            'name' => 'Second Tenant',
            'slug' => 'second-tenant',
            'is_active' => true,
            'plan' => 'basic',
        ]);

        $response = $this->actingAs($this->adminUser, 'api')
            ->getJson('/api/v1/tenants')
            ->assertStatus(200)
            ->assertJsonStructure(['data']);

        $this->assertArrayNotHasKey('meta', $response->json());
        $this->assertArrayNotHasKey('links', $response->json());
        $this->assertCount(2, $response->json('data'));
    }
}
