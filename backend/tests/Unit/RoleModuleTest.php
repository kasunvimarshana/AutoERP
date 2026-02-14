<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Modules\Role\Models\Role;
use App\Modules\Permission\Models\Permission;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoleModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'PermissionsSeeder']);
    }

    public function test_can_create_role_with_tenant(): void
    {
        $tenant = Tenant::create([
            'id' => 'test-tenant-id',
            'name' => 'Test Tenant',
        ]);

        $role = Role::create([
            'name' => 'test-role',
            'guard_name' => 'web',
            'tenant_id' => $tenant->id,
        ]);

        $this->assertDatabaseHas('roles', [
            'name' => 'test-role',
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_can_assign_permissions_to_role(): void
    {
        $tenant = Tenant::create([
            'id' => 'test-tenant-id',
            'name' => 'Test Tenant',
        ]);

        $role = Role::create([
            'name' => 'manager',
            'guard_name' => 'web',
            'tenant_id' => $tenant->id,
        ]);

        $permissions = Permission::whereIn('name', ['users.view', 'users.create'])->get();
        $role->syncPermissions($permissions);

        $this->assertTrue($role->hasPermissionTo('users.view'));
        $this->assertTrue($role->hasPermissionTo('users.create'));
        $this->assertFalse($role->hasPermissionTo('users.delete'));
    }

    public function test_permissions_are_grouped_by_module(): void
    {
        $permissions = Permission::all()->groupBy('module');

        $this->assertNotEmpty($permissions);
        $this->assertArrayHasKey('users', $permissions->toArray());
        $this->assertArrayHasKey('roles', $permissions->toArray());
        $this->assertArrayHasKey('permissions', $permissions->toArray());
    }
}
