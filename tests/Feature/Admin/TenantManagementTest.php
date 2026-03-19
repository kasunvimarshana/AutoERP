<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

final class TenantManagementTest extends TestCase
{
    use RefreshDatabase;

    protected bool $setUpPassportClient = true;

    private function adminToken(): string
    {
        $user = User::factory()->create([
            'email'    => 'superadmin@example.com',
            'password' => Hash::make('Password123'),
            'status'   => 'active',
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email'    => 'superadmin@example.com',
            'password' => 'Password123',
        ]);

        return (string) $loginResponse->json('data.access_token');
    }

    public function test_can_create_tenant(): void
    {
        $token = $this->adminToken();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/admin/tenants', [
                'name'          => 'Acme Corporation',
                'slug'          => 'acme-corp',
                'status'        => 'active',
                'timezone'      => 'America/New_York',
                'locale'        => 'en',
                'currency_code' => 'USD',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Acme Corporation')
            ->assertJsonPath('data.slug', 'acme-corp');
    }

    public function test_slug_must_be_unique(): void
    {
        Tenant::create([
            'id'   => (string) Str::uuid(),
            'name' => 'Existing Tenant',
            'slug' => 'existing-slug',
        ]);

        $token = $this->adminToken();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/admin/tenants', [
                'name' => 'Another Tenant',
                'slug' => 'existing-slug',
            ]);

        $response->assertStatus(422);
    }

    public function test_can_show_tenant(): void
    {
        $tenant = Tenant::create([
            'id'   => (string) Str::uuid(),
            'name' => 'Show Me Tenant',
            'slug' => 'show-me',
        ]);

        $token = $this->adminToken();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/admin/tenants/{$tenant->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.slug', 'show-me');
    }

    public function test_show_returns_404_for_unknown_tenant(): void
    {
        $token = $this->adminToken();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/tenants/' . Str::uuid());

        $response->assertStatus(404);
    }

    public function test_can_update_tenant(): void
    {
        $tenant = Tenant::create([
            'id'   => (string) Str::uuid(),
            'name' => 'Old Name',
            'slug' => 'old-slug',
        ]);

        $token = $this->adminToken();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/admin/tenants/{$tenant->id}", [
                'name'   => 'New Name',
                'status' => 'active',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'New Name');
    }

    public function test_unauthenticated_cannot_create_tenant(): void
    {
        $response = $this->postJson('/api/v1/admin/tenants', [
            'name' => 'Hacker Tenant',
            'slug' => 'hacker',
        ]);

        $response->assertStatus(401);
    }
}
