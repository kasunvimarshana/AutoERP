<?php

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FileManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::factory()->create(['status' => TenantStatus::Active]);
        $this->user = User::factory()->create(['tenant_id' => $tenant->id]);
    }

    public function test_unauthenticated_cannot_list_files(): void
    {
        $this->getJson('/api/v1/files')->assertStatus(401);
    }

    public function test_authenticated_user_without_permission_cannot_list_files(): void
    {
        $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/files')
            ->assertStatus(403);
    }
}
