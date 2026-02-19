<?php

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::factory()->create(['status' => TenantStatus::Active]);
        $this->user = User::factory()->create(['tenant_id' => $tenant->id]);
    }

    public function test_unauthenticated_cannot_list_price_lists(): void
    {
        $this->getJson('/api/v1/price-lists')->assertStatus(401);
    }

    public function test_authenticated_user_without_permission_cannot_list_price_lists(): void
    {
        $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/price-lists')
            ->assertStatus(403);
    }

    public function test_unauthenticated_cannot_create_price_list(): void
    {
        $this->postJson('/api/v1/price-lists', [])->assertStatus(401);
    }
}
