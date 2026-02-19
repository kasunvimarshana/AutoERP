<?php

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::factory()->create(['status' => TenantStatus::Active]);
        $this->user = User::factory()->create(['tenant_id' => $tenant->id]);
    }

    public function test_unauthenticated_cannot_access_contacts(): void
    {
        $this->getJson('/api/v1/crm/contacts')->assertStatus(401);
    }

    public function test_authenticated_user_can_list_contacts(): void
    {
        $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/crm/contacts')
            ->assertStatus(200);
    }

    public function test_authenticated_user_can_list_leads(): void
    {
        $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/crm/leads')
            ->assertStatus(200);
    }

    public function test_authenticated_user_can_list_opportunities(): void
    {
        $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/crm/opportunities')
            ->assertStatus(200);
    }
}
