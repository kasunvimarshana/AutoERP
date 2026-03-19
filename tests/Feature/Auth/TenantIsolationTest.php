<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domain\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

final class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $setUpPassportClient = true;

    public function test_user_in_one_tenant_cannot_login_with_another_tenants_credentials(): void
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

        User::factory()->create([
            'email'     => 'user@example.com',
            'password'  => Hash::make('Password123'),
            'tenant_id' => $tenantA->id,
            'status'    => 'active',
        ]);

        // Try to login with tenant B's ID while user belongs to tenant A
        $response = $this->postJson('/api/v1/auth/login', [
            'email'     => 'user@example.com',
            'password'  => 'Password123',
            'tenant_id' => $tenantB->id,
        ]);

        $response->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    public function test_user_can_login_with_correct_tenant_id(): void
    {
        $tenant = Tenant::create([
            'id'   => (string) Str::uuid(),
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
        ]);

        User::factory()->create([
            'email'     => 'user@example.com',
            'password'  => Hash::make('Password123'),
            'tenant_id' => $tenant->id,
            'status'    => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'     => 'user@example.com',
            'password'  => 'Password123',
            'tenant_id' => $tenant->id,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}
