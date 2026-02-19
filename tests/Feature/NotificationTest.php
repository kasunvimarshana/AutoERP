<?php

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::factory()->create(['status' => TenantStatus::Active]);
        $this->user = User::factory()->create(['tenant_id' => $tenant->id]);
    }

    public function test_unauthenticated_cannot_list_templates(): void
    {
        $this->getJson('/api/v1/notifications/templates')->assertStatus(401);
    }

    public function test_authenticated_user_can_list_templates(): void
    {
        $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/notifications/templates')
            ->assertStatus(200);
    }

    public function test_authenticated_user_can_list_notification_logs(): void
    {
        $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/notifications/logs')
            ->assertStatus(200);
    }
}
