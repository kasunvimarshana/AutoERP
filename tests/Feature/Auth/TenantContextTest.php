<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domain\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

final class TenantContextTest extends TestCase
{
    use RefreshDatabase;

    protected bool $setUpPassportClient = true;

    public function test_cross_tenant_access_is_forbidden(): void
    {
        $tenantA = Tenant::create([
            'id'   => (string) Str::uuid(),
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        $tenantB = Tenant::create([
            'id'   => (string) Str::uuid(),
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
        ]);

        $userA = User::factory()->create([
            'email'     => 'user@tenant-a.com',
            'password'  => Hash::make('Password123'),
            'tenant_id' => $tenantA->id,
            'status'    => 'active',
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email'     => 'user@tenant-a.com',
            'password'  => 'Password123',
            'tenant_id' => $tenantA->id,
        ]);

        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('data.access_token');

        // User belongs to tenant A but tries to access with tenant B header
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Tenant-ID'   => $tenantB->id,
        ])->getJson('/api/v1/user/profile');

        $response->assertStatus(403)
            ->assertJsonPath('error.code', 'TENANT_MISMATCH');
    }

    public function test_correct_tenant_context_allows_access(): void
    {
        $tenant = Tenant::create([
            'id'   => (string) Str::uuid(),
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        $user = User::factory()->create([
            'email'     => 'user@tenant-a.com',
            'password'  => Hash::make('Password123'),
            'tenant_id' => $tenant->id,
            'status'    => 'active',
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email'     => 'user@tenant-a.com',
            'password'  => 'Password123',
            'tenant_id' => $tenant->id,
        ]);

        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('data.access_token');

        // Access with matching tenant header
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Tenant-ID'   => $tenant->id,
        ])->getJson('/api/v1/user/profile');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}
