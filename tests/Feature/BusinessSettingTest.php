<?php

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BusinessSettingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['status' => TenantStatus::Active]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    public function test_unauthenticated_cannot_access_settings(): void
    {
        $this->getJson('/api/v1/settings')->assertStatus(401);
    }

    public function test_authenticated_user_without_permission_gets_403(): void
    {
        $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/settings')
            ->assertStatus(403);
    }

    public function test_user_with_permission_can_list_settings(): void
    {
        $perm = Permission::firstOrCreate(['name' => 'settings.view', 'guard_name' => 'api']);
        $this->user->givePermissionTo($perm);

        $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/settings')
            ->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_user_can_upsert_settings(): void
    {
        $viewPerm = Permission::firstOrCreate(['name' => 'settings.view', 'guard_name' => 'api']);
        $editPerm = Permission::firstOrCreate(['name' => 'settings.edit', 'guard_name' => 'api']);
        $this->user->givePermissionTo([$viewPerm, $editPerm]);

        $this->actingAs($this->user, 'api')
            ->putJson('/api/v1/settings', [
                'settings' => [
                    ['key' => 'company_name', 'value' => 'Acme Corp', 'group' => 'general'],
                    ['key' => 'currency', 'value' => 'USD', 'group' => 'general'],
                ],
            ])
            ->assertStatus(200)
            ->assertJsonPath('message', 'Settings saved.');

        $this->assertDatabaseHas('business_settings', [
            'tenant_id' => $this->tenant->id,
            'key' => 'company_name',
            'value' => 'Acme Corp',
        ]);
    }

    public function test_upsert_updates_existing_setting(): void
    {
        $editPerm = Permission::firstOrCreate(['name' => 'settings.edit', 'guard_name' => 'api']);
        $this->user->givePermissionTo($editPerm);

        // Set initial
        $this->actingAs($this->user, 'api')
            ->putJson('/api/v1/settings', [
                'settings' => [['key' => 'timezone', 'value' => 'UTC']],
            ]);

        // Update
        $this->actingAs($this->user, 'api')
            ->putJson('/api/v1/settings', [
                'settings' => [['key' => 'timezone', 'value' => 'Asia/Colombo']],
            ]);

        $this->assertDatabaseHas('business_settings', [
            'tenant_id' => $this->tenant->id,
            'key' => 'timezone',
            'value' => 'Asia/Colombo',
        ]);

        // Only one row for the same key
        $this->assertDatabaseCount('business_settings', 1);
    }

    public function test_user_can_delete_setting(): void
    {
        $editPerm = Permission::firstOrCreate(['name' => 'settings.edit', 'guard_name' => 'api']);
        $this->user->givePermissionTo($editPerm);

        // Create setting first
        $this->actingAs($this->user, 'api')
            ->putJson('/api/v1/settings', [
                'settings' => [['key' => 'to_delete', 'value' => 'yes']],
            ]);

        $this->assertDatabaseHas('business_settings', ['key' => 'to_delete']);

        $this->actingAs($this->user, 'api')
            ->deleteJson('/api/v1/settings/to_delete')
            ->assertStatus(204);

        $this->assertDatabaseMissing('business_settings', ['key' => 'to_delete']);
    }
}
