<?php

declare(strict_types=1);

namespace Modules\Tenancy\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tenancy\Entities\Tenant;
use Tests\TestCase;

class TenantControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create user with tenant management permissions (no tenant_id for super admin)
        $this->user = User::factory()->create([
            'tenant_id' => null, // Super admin not tied to specific tenant
            'permissions' => [
                'tenant.view',
                'tenant.create',
                'tenant.update',
                'tenant.delete',
                'tenant.activate',
                'tenant.deactivate',
                'tenant.suspend',
            ],
        ]);
    }

    public function test_it_lists_tenants(): void
    {
        Tenant::factory()->count(5)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/tenants');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'domain',
                        'status',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    public function test_it_creates_tenant(): void
    {
        $data = [
            'name' => 'New Company',
            'slug' => 'new-company',
            'domain' => 'new-company.example.com',
            'database' => 'tenant_new_company',
            'schema' => 'new_company',
            'status' => 'active',
            'settings' => [
                'timezone' => 'UTC',
                'locale' => 'en',
                'currency' => 'USD',
            ],
            'features' => ['sales', 'crm'],
            'limits' => [
                'users' => 10,
                'storage_mb' => 5120,
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/tenants', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New Company')
            ->assertJsonPath('data.slug', 'new-company')
            ->assertJsonPath('data.domain', 'new-company.example.com');

        $this->assertDatabaseHas('tenants', [
            'name' => 'New Company',
            'slug' => 'new-company',
            'domain' => 'new-company.example.com',
        ]);
    }

    public function test_it_shows_single_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/tenants/{$tenant->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $tenant->id)
            ->assertJsonPath('data.name', $tenant->name)
            ->assertJsonPath('data.slug', $tenant->slug);
    }

    public function test_it_returns_404_when_tenant_not_found(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/tenants/99999');

        $response->assertStatus(404)
            ->assertJson(['message' => 'Tenant not found']);
    }

    public function test_it_updates_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $updateData = [
            'name' => 'Updated Company Name',
            'status' => 'suspended',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/tenants/{$tenant->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Company Name')
            ->assertJsonPath('data.status', 'suspended');

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'Updated Company Name',
            'status' => 'suspended',
        ]);
    }

    public function test_it_deletes_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/tenants/{$tenant->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Tenant deleted successfully']);

        $this->assertDatabaseMissing('tenants', [
            'id' => $tenant->id,
        ]);
    }

    public function test_it_searches_tenants(): void
    {
        Tenant::factory()->create(['name' => 'Acme Corporation']);
        Tenant::factory()->create(['name' => 'Tech Solutions Inc']);
        Tenant::factory()->create(['name' => 'Acme Industries']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/tenants/search?q=Acme');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(2, $data);

        foreach ($data as $tenant) {
            $this->assertStringContainsString('Acme', $tenant['name']);
        }
    }

    public function test_it_gets_active_tenants(): void
    {
        Tenant::factory()->count(3)->create(['status' => 'active']);
        Tenant::factory()->count(2)->suspended()->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/tenants/active');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(3, $data);

        foreach ($data as $tenant) {
            $this->assertEquals('active', $tenant['status']);
        }
    }

    public function test_it_activates_tenant(): void
    {
        $tenant = Tenant::factory()->suspended()->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/tenants/{$tenant->id}/activate");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'status' => 'active',
        ]);
    }

    public function test_it_deactivates_tenant(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'active']);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/tenants/{$tenant->id}/deactivate");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'inactive');

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'status' => 'inactive',
        ]);
    }

    public function test_it_suspends_tenant(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'active']);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/tenants/{$tenant->id}/suspend");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'suspended');

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'status' => 'suspended',
        ]);
    }

    public function test_it_validates_required_fields_on_create(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/tenants', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'slug', 'domain']);
    }

    public function test_it_validates_unique_slug(): void
    {
        Tenant::factory()->create(['slug' => 'existing-slug']);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/tenants', [
                'name' => 'Test Company',
                'slug' => 'existing-slug',
                'domain' => 'test.example.com',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_it_validates_unique_domain(): void
    {
        Tenant::factory()->create(['domain' => 'existing.example.com']);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/tenants', [
                'name' => 'Test Company',
                'slug' => 'test-company',
                'domain' => 'existing.example.com',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['domain']);
    }

    public function test_unauthorized_user_cannot_list_tenants(): void
    {
        $unauthorizedUser = User::factory()->create([
            'tenant_id' => null,
            'permissions' => [],
        ]);

        $response = $this->actingAs($unauthorizedUser)
            ->getJson('/api/v1/tenants');

        $response->assertStatus(403);
    }

    public function test_unauthorized_user_cannot_create_tenant(): void
    {
        $unauthorizedUser = User::factory()->create([
            'tenant_id' => null,
            'permissions' => [],
        ]);

        $response = $this->actingAs($unauthorizedUser)
            ->postJson('/api/v1/tenants', [
                'name' => 'Test',
                'slug' => 'test',
                'domain' => 'test.example.com',
            ]);

        $response->assertStatus(403);
    }

    public function test_unauthorized_user_cannot_update_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $unauthorizedUser = User::factory()->create([
            'tenant_id' => null,
            'permissions' => [],
        ]);

        $response = $this->actingAs($unauthorizedUser)
            ->putJson("/api/v1/tenants/{$tenant->id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(403);
    }

    public function test_unauthorized_user_cannot_delete_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $unauthorizedUser = User::factory()->create([
            'tenant_id' => null,
            'permissions' => [],
        ]);

        $response = $this->actingAs($unauthorizedUser)
            ->deleteJson("/api/v1/tenants/{$tenant->id}");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_endpoints(): void
    {
        $response = $this->getJson('/api/v1/tenants');
        $response->assertStatus(401);
    }

    public function test_it_supports_pagination(): void
    {
        Tenant::factory()->count(25)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/tenants?per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links',
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);

        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(25, $response->json('meta.total'));
        $this->assertEquals(10, $response->json('meta.per_page'));
    }

    public function test_it_returns_tenant_with_settings(): void
    {
        $tenant = Tenant::factory()->create([
            'settings' => [
                'timezone' => 'America/New_York',
                'locale' => 'en',
                'currency' => 'USD',
            ],
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/tenants/{$tenant->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.settings.timezone', 'America/New_York')
            ->assertJsonPath('data.settings.locale', 'en')
            ->assertJsonPath('data.settings.currency', 'USD');
    }

    public function test_it_returns_tenant_with_features(): void
    {
        $tenant = Tenant::factory()->create([
            'features' => ['sales', 'crm', 'inventory'],
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/tenants/{$tenant->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.features', ['sales', 'crm', 'inventory']);
    }

    public function test_it_returns_tenant_with_limits(): void
    {
        $tenant = Tenant::factory()->smallBusiness()->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/tenants/{$tenant->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.limits.users', 5)
            ->assertJsonPath('data.limits.storage_mb', 1024);
    }

    public function test_it_handles_search_with_no_results(): void
    {
        Tenant::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/tenants/search?q=NonexistentCompany');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }

    public function test_it_handles_empty_search_query(): void
    {
        Tenant::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/tenants/search?q=');

        $response->assertStatus(200);
    }
}
