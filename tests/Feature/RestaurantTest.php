<?php

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::factory()->create(['status' => TenantStatus::Active]);
        $this->user = User::factory()->create(['tenant_id' => $tenant->id]);
    }

    public function test_unauthenticated_cannot_list_tables(): void
    {
        $this->getJson('/api/v1/restaurant/tables')->assertStatus(401);
    }

    public function test_authenticated_user_can_list_tables(): void
    {
        $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/restaurant/tables')
            ->assertStatus(200);
    }

    public function test_unauthenticated_cannot_list_modifier_sets(): void
    {
        $this->getJson('/api/v1/restaurant/modifier-sets')->assertStatus(401);
    }

    public function test_authenticated_user_can_list_modifier_sets(): void
    {
        $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/restaurant/modifier-sets')
            ->assertStatus(200);
    }

    public function test_unauthenticated_cannot_list_bookings(): void
    {
        $this->getJson('/api/v1/restaurant/bookings')->assertStatus(401);
    }

    public function test_authenticated_user_can_list_bookings(): void
    {
        $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/restaurant/bookings')
            ->assertStatus(200);
    }
}
