<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    public function test_super_admin_can_list_tenants(): void
    {
        Tenant::factory()->count(3)->create();

        $this->getJsonAsSuperAdmin('/api/v1/tenants')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'name', 'slug', 'plan', 'status']],
                'meta' => ['total'],
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    */

    public function test_super_admin_can_show_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $this->getJsonAsSuperAdmin("/api/v1/tenants/{$tenant->id}")
            ->assertOk()
            ->assertJsonPath('id', $tenant->id);
    }

    public function test_show_nonexistent_tenant_returns_404(): void
    {
        $this->getJsonAsSuperAdmin('/api/v1/tenants/99999')
            ->assertNotFound();
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function test_super_admin_can_create_tenant(): void
    {
        $payload = [
            'name' => 'New Tenant',
            'slug' => 'new-tenant',
            'plan' => 'professional',
        ];

        $this->postJsonAsSuperAdmin('/api/v1/tenants', $payload)
            ->assertCreated()
            ->assertJsonPath('name', 'New Tenant')
            ->assertJsonPath('slug', 'new-tenant');
    }

    public function test_create_tenant_validates_required_fields(): void
    {
        $this->postJsonAsSuperAdmin('/api/v1/tenants', [])
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['name']]);
    }

    public function test_create_tenant_rejects_duplicate_slug(): void
    {
        Tenant::factory()->create(['slug' => 'taken-slug']);

        $this->postJsonAsSuperAdmin('/api/v1/tenants', [
            'name' => 'Another',
            'slug' => 'taken-slug',
        ])->assertUnprocessable();
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function test_super_admin_can_update_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $this->putJsonAsSuperAdmin("/api/v1/tenants/{$tenant->id}", ['name' => 'Renamed'])
            ->assertOk()
            ->assertJsonPath('name', 'Renamed');
    }

    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    public function test_super_admin_can_delete_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $this->deleteJsonAsSuperAdmin("/api/v1/tenants/{$tenant->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('tenants', ['id' => $tenant->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function superAdminHeaders(): array
    {
        return ['Accept' => 'application/json'];
    }

    private function getJsonAsSuperAdmin(string $uri): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders($this->superAdminHeaders())->getJson($uri);
    }

    private function postJsonAsSuperAdmin(string $uri, array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders($this->superAdminHeaders())->postJson($uri, $data);
    }

    private function putJsonAsSuperAdmin(string $uri, array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders($this->superAdminHeaders())->putJson($uri, $data);
    }

    private function deleteJsonAsSuperAdmin(string $uri): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders($this->superAdminHeaders())->deleteJson($uri);
    }
}
