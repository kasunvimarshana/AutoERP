<?php

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::factory()->create(['status' => TenantStatus::Active]);
        $this->user = User::factory()->create(['tenant_id' => $tenant->id]);
    }

    public function test_unauthenticated_cannot_access_reports(): void
    {
        $this->getJson('/api/v1/reports/inventory-summary')->assertStatus(401);
    }

    public function test_unauthenticated_cannot_access_top_products(): void
    {
        $this->getJson('/api/v1/reports/top-products')->assertStatus(401);
    }
}
