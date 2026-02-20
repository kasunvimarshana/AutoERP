<?php

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SalesCommissionAgentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::factory()->create(['status' => TenantStatus::Active]);
        $this->user = User::factory()->create(['tenant_id' => $tenant->id]);
    }

    public function test_unauthenticated_cannot_list_commission_agents(): void
    {
        $this->getJson('/api/v1/sales-commission-agents')->assertStatus(401);
    }

    public function test_authenticated_user_without_permission_cannot_list_commission_agents(): void
    {
        $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/sales-commission-agents')
            ->assertStatus(403);
    }

    public function test_authenticated_user_with_permission_can_list_commission_agents(): void
    {
        $perm = Permission::firstOrCreate(['name' => 'users.view', 'guard_name' => 'api']);
        $this->user->givePermissionTo($perm);

        $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/sales-commission-agents')
            ->assertStatus(200);
    }

    public function test_unauthenticated_cannot_get_agent_total_sell(): void
    {
        $this->getJson('/api/v1/sales-commission-agents/'.$this->user->id.'/total-sell')->assertStatus(401);
    }

    public function test_unauthenticated_cannot_get_agent_total_commission(): void
    {
        $this->getJson('/api/v1/sales-commission-agents/'.$this->user->id.'/total-commission')->assertStatus(401);
    }
}
