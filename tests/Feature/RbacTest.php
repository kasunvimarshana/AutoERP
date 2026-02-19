<?php

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::factory()->create(['status' => TenantStatus::Active]);
        $this->user = User::factory()->create(['tenant_id' => $tenant->id]);
    }

    public function test_unauthenticated_cannot_list_roles(): void
    {
        $this->getJson('/api/v1/roles')->assertStatus(401);
    }

    public function test_unauthenticated_cannot_list_permissions(): void
    {
        $this->getJson('/api/v1/roles/permissions')->assertStatus(401);
    }
}
