<?php

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::factory()->create(['status' => TenantStatus::Active]);
        $this->user = User::factory()->create(['tenant_id' => $tenant->id]);
    }

    public function test_unauthenticated_cannot_list_stock_adjustments(): void
    {
        $this->getJson('/api/v1/stock-adjustments')->assertStatus(401);
    }

    public function test_unauthenticated_cannot_create_stock_adjustment(): void
    {
        $this->postJson('/api/v1/stock-adjustments', [])->assertStatus(401);
    }
}
