<?php

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrinterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::factory()->create(['status' => TenantStatus::Active]);
        $this->user = User::factory()->create(['tenant_id' => $tenant->id]);
    }

    public function test_unauthenticated_cannot_list_printers(): void
    {
        $this->getJson('/api/v1/printers')->assertStatus(401);
    }

    public function test_authenticated_user_can_list_printers(): void
    {
        $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/printers')
            ->assertStatus(200);
    }

    public function test_can_get_capability_profiles(): void
    {
        $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/printers/capability-profiles')
            ->assertStatus(200)
            ->assertJsonStructure(['default', 'simple']);
    }

    public function test_can_get_connection_types(): void
    {
        $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/printers/connection-types')
            ->assertStatus(200)
            ->assertJsonStructure(['network', 'windows']);
    }

    public function test_unauthenticated_cannot_create_printer(): void
    {
        $this->postJson('/api/v1/printers', [])->assertStatus(401);
    }
}
