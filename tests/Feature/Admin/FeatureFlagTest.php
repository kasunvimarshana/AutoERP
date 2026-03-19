<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

final class FeatureFlagTest extends TestCase
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
            'name' => 'Flag Tenant',
            'slug' => 'flag-tenant',
        ]);

        $this->tenantId = $tenant->id;

        User::factory()->create([
            'email'     => 'admin@flag-tenant.com',
            'password'  => Hash::make('Password123'),
            'tenant_id' => $tenant->id,
            'status'    => 'active',
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email'     => 'admin@flag-tenant.com',
            'password'  => 'Password123',
            'tenant_id' => $tenant->id,
        ]);

        $this->token = (string) $loginResponse->json('data.access_token');
    }

    public function test_can_create_feature_flag(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/admin/feature-flags', [
                'name'       => 'new-dashboard',
                'is_enabled' => true,
                'tenant_id'  => $this->tenantId,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'new-dashboard')
            ->assertJsonPath('data.is_enabled', true);
    }

    public function test_can_list_feature_flags(): void
    {
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/admin/feature-flags', [
                'name'       => 'flag-a',
                'is_enabled' => true,
                'tenant_id'  => $this->tenantId,
            ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/admin/feature-flags?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertNotEmpty($response->json('data'));
    }

    public function test_can_check_feature_flag_enabled(): void
    {
        // Create the flag for this tenant
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/admin/feature-flags', [
                'name'       => 'beta-feature',
                'is_enabled' => true,
                'tenant_id'  => $this->tenantId,
            ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/feature-flags/beta-feature/check');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'beta-feature')
            ->assertJsonPath('data.is_enabled', true);
    }

    public function test_can_check_feature_flag_disabled(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/feature-flags/nonexistent-feature/check');

        $response->assertStatus(200)
            ->assertJsonPath('data.is_enabled', false);
    }

    public function test_can_delete_feature_flag(): void
    {
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/admin/feature-flags', [
                'name'       => 'to-delete',
                'is_enabled' => false,
                'tenant_id'  => $this->tenantId,
            ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/v1/admin/feature-flags/to-delete?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}
