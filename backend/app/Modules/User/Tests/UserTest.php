<?php

namespace App\Modules\User\Tests;

use App\Modules\Tenant\Models\Tenant;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['is_active' => true]);

        $this->adminUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role'      => 'admin',
        ]);
        $this->adminUser->assignRole('admin');
    }

    public function test_can_list_users(): void
    {
        Passport::actingAs($this->adminUser);

        User::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->getJson('/api/v1/users', ['X-Tenant-ID' => $this->tenant->id]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'meta']);
    }

    public function test_can_create_user(): void
    {
        Passport::actingAs($this->adminUser);

        $response = $this->postJson('/api/v1/users', [
            'name'                  => 'New User',
            'email'                 => 'newuser@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'staff',
        ], ['X-Tenant-ID' => $this->tenant->id]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.email', 'newuser@example.com');
    }

    public function test_can_update_user(): void
    {
        Passport::actingAs($this->adminUser);

        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->putJson("/api/v1/users/{$user->id}", [
            'name' => 'Updated Name',
        ], ['X-Tenant-ID' => $this->tenant->id]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_can_delete_user(): void
    {
        Passport::actingAs($this->adminUser);

        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->deleteJson("/api/v1/users/{$user->id}", [], ['X-Tenant-ID' => $this->tenant->id]);

        $response->assertStatus(204);
    }

    public function test_cannot_access_without_auth(): void
    {
        $response = $this->getJson('/api/v1/users', ['X-Tenant-ID' => $this->tenant->id]);

        $response->assertStatus(401);
    }
}
