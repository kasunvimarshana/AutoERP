<?php

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceLayoutTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::factory()->create(['status' => TenantStatus::Active]);
        $this->user = User::factory()->create(['tenant_id' => $tenant->id]);
    }

    public function test_unauthenticated_cannot_list_invoice_layouts(): void
    {
        $this->getJson('/api/v1/invoice-layouts')->assertStatus(401);
    }

    public function test_authenticated_user_can_list_invoice_layouts(): void
    {
        $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/invoice-layouts')
            ->assertStatus(200);
    }

    public function test_unauthenticated_cannot_create_invoice_layout(): void
    {
        $this->postJson('/api/v1/invoice-layouts', [])->assertStatus(401);
    }
}
