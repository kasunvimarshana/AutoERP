<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Tenant;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_tenant(): void
    {
        $response = $this->postJson('/api/v1/tenants', [
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'plan_code' => 'free',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name', 'slug', 'status', 'plan_code', 'currency', 'created_at'],
                'errors',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Test Tenant')
            ->assertJsonPath('data.slug', 'test-tenant');
    }

    public function test_default_currency_is_lkr(): void
    {
        $response = $this->postJson('/api/v1/tenants', [
            'name' => 'LKR Tenant',
            'slug' => 'lkr-tenant',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.currency', 'LKR');
    }

    public function test_can_create_tenant_with_custom_currency(): void
    {
        $response = $this->postJson('/api/v1/tenants', [
            'name' => 'USD Tenant',
            'slug' => 'usd-tenant',
            'currency' => 'USD',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.currency', 'USD');
    }

    public function test_currency_must_be_supported(): void
    {
        $response = $this->postJson('/api/v1/tenants', [
            'name' => 'Bad Currency Tenant',
            'slug' => 'bad-currency-tenant',
            'currency' => 'XYZ',
        ]);

        $response->assertStatus(422);
    }

    public function test_can_list_tenants(): void
    {
        $this->postJson('/api/v1/tenants', ['name' => 'Tenant A', 'slug' => 'tenant-a']);
        $this->postJson('/api/v1/tenants', ['name' => 'Tenant B', 'slug' => 'tenant-b']);

        $response = $this->getJson('/api/v1/tenants');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success', 'message', 'data', 'errors',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_slug_must_be_unique(): void
    {
        $this->postJson('/api/v1/tenants', ['name' => 'Tenant A', 'slug' => 'my-tenant']);

        $response = $this->postJson('/api/v1/tenants', ['name' => 'Tenant B', 'slug' => 'my-tenant']);

        $response->assertStatus(422);
    }

    public function test_can_get_tenant_by_id(): void
    {
        $createResponse = $this->postJson('/api/v1/tenants', [
            'name' => 'Single Tenant',
            'slug' => 'single-tenant',
        ]);
        $id = $createResponse->json('data.id');

        $response = $this->getJson("/api/v1/tenants/{$id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $id);
    }

    public function test_returns_404_for_nonexistent_tenant(): void
    {
        $response = $this->getJson('/api/v1/tenants/99999');

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }
}
