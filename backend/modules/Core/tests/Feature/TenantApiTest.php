<?php

namespace Modules\Core\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Core\Models\Tenant;
use Tests\TestCase;

class TenantApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        // Assuming you have a role system - adjust as needed
    }

    public function test_can_list_tenants()
    {
        Sanctum::actingAs($this->admin);

        Tenant::factory()->count(3)->create();

        $response = $this->getJson('/api/tenants');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => ['id', 'uuid', 'name', 'domain', 'status'],
                    ],
                ],
            ]);
    }

    public function test_can_create_tenant()
    {
        Sanctum::actingAs($this->admin);

        $data = [
            'name' => 'New Tenant',
            'domain' => 'newtenant.example.com',
            'plan' => 'premium',
        ];

        $response = $this->postJson('/api/tenants', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'uuid', 'name', 'domain'],
            ]);

        $this->assertDatabaseHas('tenants', [
            'name' => 'New Tenant',
            'domain' => 'newtenant.example.com',
        ]);
    }

    public function test_can_show_tenant()
    {
        Sanctum::actingAs($this->admin);

        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'domain' => 'test.example.com',
        ]);

        $response = $this->getJson("/api/tenants/{$tenant->uuid}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'uuid' => $tenant->uuid,
                    'name' => 'Test Tenant',
                ],
            ]);
    }

    public function test_can_update_tenant()
    {
        Sanctum::actingAs($this->admin);

        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'domain' => 'test.example.com',
        ]);

        $response = $this->putJson("/api/tenants/{$tenant->uuid}", [
            'name' => 'Updated Tenant',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'Updated Tenant',
        ]);
    }

    public function test_can_suspend_tenant()
    {
        Sanctum::actingAs($this->admin);

        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'domain' => 'test.example.com',
            'status' => 'active',
        ]);

        $response = $this->postJson("/api/tenants/{$tenant->uuid}/suspend");

        $response->assertStatus(200);

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'status' => 'suspended',
        ]);
    }

    public function test_can_activate_tenant()
    {
        Sanctum::actingAs($this->admin);

        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'domain' => 'test.example.com',
            'status' => 'suspended',
        ]);

        $response = $this->postJson("/api/tenants/{$tenant->uuid}/activate");

        $response->assertStatus(200);

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'status' => 'active',
        ]);
    }

    public function test_validation_fails_for_duplicate_domain()
    {
        Sanctum::actingAs($this->admin);

        Tenant::create([
            'name' => 'Existing Tenant',
            'domain' => 'existing.example.com',
        ]);

        $response = $this->postJson('/api/tenants', [
            'name' => 'New Tenant',
            'domain' => 'existing.example.com',
        ]);

        $response->assertStatus(422);
    }
}
