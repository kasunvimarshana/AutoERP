<?php

namespace Modules\IAM\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Modules\Core\Services\TenantContext;
use Modules\IAM\DTOs\RoleDTO;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Role;
use Modules\IAM\Repositories\RoleRepository;
use Modules\IAM\Services\RoleService;
use Tests\TestCase;

class RoleServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RoleService $roleService;

    protected TenantContext $tenantContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantContext = Mockery::mock(TenantContext::class);
        $this->tenantContext->shouldReceive('getTenantId')->andReturn(1);
        $this->tenantContext->shouldReceive('hasTenant')->andReturn(true);

        $roleRepository = new RoleRepository($this->tenantContext);
        $this->roleService = new RoleService($roleRepository, $this->tenantContext);

        $this->artisan('migrate:fresh');
        $this->seed(\Modules\IAM\Database\Seeders\IAMSeeder::class);
    }

    public function test_can_create_role(): void
    {
        $dto = new RoleDTO([
            'name' => 'test-role',
            'description' => 'Test Role',
            'permissions' => [],
        ]);

        $role = $this->roleService->create($dto);

        $this->assertInstanceOf(Role::class, $role);
        $this->assertEquals('test-role', $role->name);
        $this->assertEquals(1, $role->tenant_id);
    }

    public function test_cannot_create_duplicate_role(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        Role::create([
            'name' => 'test-role',
            'guard_name' => 'web',
            'tenant_id' => 1,
        ]);

        $dto = new RoleDTO([
            'name' => 'test-role',
            'description' => 'Test Role',
        ]);

        $this->roleService->create($dto);
    }

    public function test_can_update_role(): void
    {
        $role = Role::create([
            'name' => 'old-role',
            'guard_name' => 'web',
            'tenant_id' => 1,
            'is_system' => false,
        ]);

        $dto = new RoleDTO([
            'name' => 'new-role',
            'description' => 'Updated Role',
        ]);

        $updated = $this->roleService->update($role->id, $dto);

        $this->assertEquals('new-role', $updated->name);
    }

    public function test_cannot_update_system_role(): void
    {
        $this->expectException(\RuntimeException::class);

        $role = Role::where('is_system', true)->first();

        $dto = new RoleDTO([
            'name' => 'modified-system-role',
        ]);

        $this->roleService->update($role->id, $dto);
    }

    public function test_can_delete_custom_role(): void
    {
        $role = Role::create([
            'name' => 'deletable-role',
            'guard_name' => 'web',
            'tenant_id' => 1,
            'is_system' => false,
        ]);

        $this->roleService->delete($role->id);

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_cannot_delete_system_role(): void
    {
        $this->expectException(\RuntimeException::class);

        $role = Role::where('is_system', true)->first();

        $this->roleService->delete($role->id);
    }

    public function test_can_assign_permissions_to_role(): void
    {
        $role = Role::create([
            'name' => 'test-role',
            'guard_name' => 'web',
            'tenant_id' => 1,
            'is_system' => false,
        ]);

        $permission = Permission::where('name', 'user.view')->first();

        $updated = $this->roleService->assignPermissions($role->id, [$permission->name]);

        $this->assertTrue($updated->hasPermissionTo($permission));
    }

    public function test_role_hierarchy_inherits_permissions(): void
    {
        $parentRole = Role::create([
            'name' => 'parent-role',
            'guard_name' => 'web',
            'tenant_id' => 1,
        ]);

        $permission = Permission::where('name', 'user.view')->first();
        $parentRole->givePermissionTo($permission);

        $childRole = Role::create([
            'name' => 'child-role',
            'guard_name' => 'web',
            'tenant_id' => 1,
            'parent_id' => $parentRole->id,
        ]);

        $allPermissions = $childRole->getAllPermissions();

        $this->assertTrue($allPermissions->contains($permission));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
