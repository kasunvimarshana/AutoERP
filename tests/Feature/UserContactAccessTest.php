<?php

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UserContactAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::factory()->create(['status' => TenantStatus::Active]);
        $this->user = User::factory()->create(['tenant_id' => $tenant->id]);
    }

    public function test_unauthenticated_cannot_list_user_contact_access(): void
    {
        $this->getJson('/api/v1/crm/users/'.$this->user->id.'/contact-access')->assertStatus(401);
    }

    public function test_authenticated_user_without_permission_cannot_list_user_contact_access(): void
    {
        $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/crm/users/'.$this->user->id.'/contact-access')
            ->assertStatus(403);
    }

    public function test_authenticated_user_with_permission_can_list_user_contact_access(): void
    {
        $perm = Permission::firstOrCreate(['name' => 'crm.contacts.view', 'guard_name' => 'api']);
        $this->user->givePermissionTo($perm);

        $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/crm/users/'.$this->user->id.'/contact-access')
            ->assertStatus(200);
    }

    public function test_unauthenticated_cannot_sync_user_contact_access(): void
    {
        $this->putJson('/api/v1/crm/users/'.$this->user->id.'/contact-access', [])->assertStatus(401);
    }

    public function test_unauthenticated_cannot_clear_user_contact_access(): void
    {
        $this->deleteJson('/api/v1/crm/users/'.$this->user->id.'/contact-access')->assertStatus(401);
    }
}
