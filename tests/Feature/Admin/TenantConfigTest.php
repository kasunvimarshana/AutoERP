<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

final class TenantConfigTest extends TestCase
{
    use RefreshDatabase;

    protected bool $setUpPassportClient = true;

    private string $tenantId = '';
    private string $token = '';

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::create([
            'id'   => (string) Str::uuid(),
            'name' => 'Config Tenant',
            'slug' => 'config-tenant',
        ]);

        $this->tenantId = $tenant->id;

        User::factory()->create([
            'email'     => 'admin@config-tenant.com',
            'password'  => Hash::make('Password123'),
            'tenant_id' => $tenant->id,
            'status'    => 'active',
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email'     => 'admin@config-tenant.com',
            'password'  => 'Password123',
            'tenant_id' => $tenant->id,
        ]);

        $this->token = (string) $loginResponse->json('data.access_token');
    }

    public function test_can_set_tenant_config(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/v1/admin/tenants/{$this->tenantId}/config/token.access_ttl_minutes", [
                'value' => '30',
                'type'  => 'integer',
                'group' => 'token',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.key', 'token.access_ttl_minutes');
    }

    public function test_can_get_tenant_config(): void
    {
        // First set a value
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/v1/admin/tenants/{$this->tenantId}/config/some.setting", [
                'value' => 'hello',
                'type'  => 'string',
            ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/admin/tenants/{$this->tenantId}/config");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_sensitive_config_value_is_masked(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/v1/admin/tenants/{$this->tenantId}/config/api.secret_key", [
                'value'        => 'super-secret-value',
                'type'         => 'string',
                'is_sensitive' => true,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.value', '***');
    }

    public function test_can_delete_tenant_config(): void
    {
        // Set first
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/v1/admin/tenants/{$this->tenantId}/config/temp.key", [
                'value' => 'temp-value',
            ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/v1/admin/tenants/{$this->tenantId}/config/temp.key");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}
