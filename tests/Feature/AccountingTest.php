<?php

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::factory()->create(['status' => TenantStatus::Active]);
        $this->user = User::factory()->create(['tenant_id' => $tenant->id]);
    }

    public function test_unauthenticated_cannot_list_accounts(): void
    {
        $this->getJson('/api/v1/accounting/accounts')->assertStatus(401);
    }

    public function test_authenticated_user_can_list_accounts(): void
    {
        $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/accounting/accounts')
            ->assertStatus(200);
    }

    public function test_authenticated_user_can_list_periods(): void
    {
        $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/accounting/periods')
            ->assertStatus(200);
    }

    public function test_authenticated_user_can_list_journal_entries(): void
    {
        $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/accounting/journal-entries')
            ->assertStatus(200);
    }
}
