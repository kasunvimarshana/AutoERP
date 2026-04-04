<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modules\Authorization\Domain\Entities\Permission;
use Modules\Authorization\Domain\Entities\Role;
use Modules\Authorization\Domain\Entities\UserRole;
use Modules\Authorization\Domain\Events\PermissionCreated;
use Modules\Authorization\Domain\Events\PermissionDeleted;
use Modules\Authorization\Domain\Events\RoleCreated;
use Modules\Authorization\Domain\Events\RoleDeleted;
use Modules\Authorization\Domain\Events\RolePermissionsSynced;
use Modules\Authorization\Domain\Events\UserRoleAssigned;
use Modules\Authorization\Application\DTOs\AssignUserRoleData;
use Modules\Authorization\Application\DTOs\PermissionData;
use Modules\Authorization\Application\DTOs\RoleData;
use Modules\Authorization\Application\DTOs\SyncPermissionsData;
use Modules\Authorization\Application\Services\AssignUserRoleService;
use Modules\Authorization\Application\Services\CreatePermissionService;
use Modules\Authorization\Application\Services\CreateRoleService;
use Modules\Authorization\Application\Services\DeletePermissionService;
use Modules\Authorization\Application\Services\DeleteRoleService;
use Modules\Authorization\Application\Services\SyncRolePermissionsService;
use Modules\Authorization\Domain\RepositoryInterfaces\PermissionRepositoryInterface;
use Modules\Authorization\Domain\RepositoryInterfaces\RoleRepositoryInterface;
use Modules\Authorization\Domain\RepositoryInterfaces\UserRoleRepositoryInterface;

class AuthorizationModuleTest extends TestCase
{
    // --- Permission entity ---

    public function test_permission_construction_stores_id(): void
    {
        $p = new Permission(id: 1, name: 'products.view');
        $this->assertSame(1, $p->id);
    }

    public function test_permission_construction_stores_name(): void
    {
        $p = new Permission(id: 2, name: 'orders.create');
        $this->assertSame('orders.create', $p->name);
    }

    public function test_permission_default_guard_name(): void
    {
        $p = new Permission(id: 1, name: 'tenants.view');
        $this->assertSame('api', $p->guardName);
    }

    public function test_permission_custom_guard_name(): void
    {
        $p = new Permission(id: 1, name: 'admin.panel', guardName: 'web');
        $this->assertSame('web', $p->guardName);
    }

    public function test_permission_description_defaults_to_null(): void
    {
        $p = new Permission(id: 1, name: 'users.delete');
        $this->assertNull($p->description);
    }

    public function test_permission_stores_description(): void
    {
        $p = new Permission(id: 1, name: 'users.delete', description: 'Delete a user');
        $this->assertSame('Delete a user', $p->description);
    }

    public function test_permission_id_can_be_null(): void
    {
        $p = new Permission(id: null, name: 'users.view');
        $this->assertNull($p->id);
    }

    // --- Role entity ---

    public function test_role_construction_stores_id(): void
    {
        $r = new Role(id: 5, tenantId: 1, name: 'admin');
        $this->assertSame(5, $r->id);
    }

    public function test_role_construction_stores_tenant_id(): void
    {
        $r = new Role(id: 1, tenantId: 42, name: 'manager');
        $this->assertSame(42, $r->tenantId);
    }

    public function test_role_construction_stores_name(): void
    {
        $r = new Role(id: 1, tenantId: 1, name: 'warehouse_staff');
        $this->assertSame('warehouse_staff', $r->name);
    }

    public function test_role_default_guard_name(): void
    {
        $r = new Role(id: 1, tenantId: 1, name: 'viewer');
        $this->assertSame('api', $r->guardName);
    }

    public function test_role_description_defaults_to_null(): void
    {
        $r = new Role(id: 1, tenantId: 1, name: 'auditor');
        $this->assertNull($r->description);
    }

    public function test_role_stores_description(): void
    {
        $r = new Role(id: 1, tenantId: 1, name: 'super_admin', description: 'Full access');
        $this->assertSame('Full access', $r->description);
    }

    public function test_role_id_can_be_null(): void
    {
        $r = new Role(id: null, tenantId: 1, name: 'pending_role');
        $this->assertNull($r->id);
    }

    // --- UserRole entity ---

    public function test_user_role_construction_stores_id(): void
    {
        $ur = new UserRole(id: 10, userId: 3, roleId: 7);
        $this->assertSame(10, $ur->id);
    }

    public function test_user_role_stores_user_id(): void
    {
        $ur = new UserRole(id: 1, userId: 15, roleId: 2);
        $this->assertSame(15, $ur->userId);
    }

    public function test_user_role_stores_role_id(): void
    {
        $ur = new UserRole(id: 1, userId: 3, roleId: 99);
        $this->assertSame(99, $ur->roleId);
    }

    public function test_user_role_id_can_be_null(): void
    {
        $ur = new UserRole(id: null, userId: 1, roleId: 1);
        $this->assertNull($ur->id);
    }

    // --- DTOs ---

    public function test_role_data_stores_all_fields(): void
    {
        $dto = new RoleData(tenantId: 5, name: 'cashier', description: 'Cashier role');
        $this->assertSame(5, $dto->tenantId);
        $this->assertSame('cashier', $dto->name);
        $this->assertSame('cashier role', strtolower($dto->description));
    }

    public function test_role_data_to_array(): void
    {
        $dto = new RoleData(tenantId: 1, name: 'admin');
        $arr = $dto->toArray();
        $this->assertArrayHasKey('tenantId', $arr);
        $this->assertArrayHasKey('name', $arr);
    }

    public function test_permission_data_stores_name(): void
    {
        $dto = new PermissionData(name: 'roles.create');
        $this->assertSame('roles.create', $dto->name);
    }

    public function test_permission_data_default_guard_name(): void
    {
        $dto = new PermissionData(name: 'roles.view');
        $this->assertSame('api', $dto->guardName);
    }

    public function test_sync_permissions_data_stores_ids(): void
    {
        $dto = new SyncPermissionsData(roleId: 3, permissionIds: [1, 2, 5]);
        $this->assertSame(3, $dto->roleId);
        $this->assertSame([1, 2, 5], $dto->permissionIds);
    }

    public function test_assign_user_role_data_stores_fields(): void
    {
        $dto = new AssignUserRoleData(tenantId: 1, userId: 10, roleId: 3);
        $this->assertSame(1, $dto->tenantId);
        $this->assertSame(10, $dto->userId);
        $this->assertSame(3, $dto->roleId);
    }

    // --- Events ---

    public function test_role_created_event_stores_tenant_and_role(): void
    {
        $event = new RoleCreated(tenantId: 2, roleId: 7);
        $this->assertSame(2, $event->tenantId);
        $this->assertSame(7, $event->roleId);
    }

    public function test_role_deleted_event_stores_tenant_and_role(): void
    {
        $event = new RoleDeleted(tenantId: 3, roleId: 11);
        $this->assertSame(3, $event->tenantId);
        $this->assertSame(11, $event->roleId);
    }

    public function test_role_permissions_synced_event_stores_permission_ids(): void
    {
        $event = new RolePermissionsSynced(tenantId: 1, roleId: 4, permissionIds: [2, 3]);
        $this->assertSame([2, 3], $event->permissionIds);
        $this->assertSame(4, $event->roleId);
    }

    public function test_permission_created_event_stores_permission_id(): void
    {
        $event = new PermissionCreated(tenantId: 0, permissionId: 99);
        $this->assertSame(99, $event->permissionId);
    }

    public function test_permission_deleted_event_stores_permission_id(): void
    {
        $event = new PermissionDeleted(tenantId: 0, permissionId: 55);
        $this->assertSame(55, $event->permissionId);
    }

    public function test_user_role_assigned_event_stores_user_and_role(): void
    {
        $event = new UserRoleAssigned(tenantId: 1, userId: 8, roleId: 2);
        $this->assertSame(8, $event->userId);
        $this->assertSame(2, $event->roleId);
        $this->assertSame(1, $event->tenantId);
    }

    // --- Service contracts exist ---

    public function test_create_role_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\Authorization\Application\Contracts\CreateRoleServiceInterface::class));
    }

    public function test_delete_role_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\Authorization\Application\Contracts\DeleteRoleServiceInterface::class));
    }

    public function test_sync_role_permissions_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\Authorization\Application\Contracts\SyncRolePermissionsServiceInterface::class));
    }

    public function test_create_permission_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\Authorization\Application\Contracts\CreatePermissionServiceInterface::class));
    }

    public function test_delete_permission_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\Authorization\Application\Contracts\DeletePermissionServiceInterface::class));
    }

    public function test_assign_user_role_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(\Modules\Authorization\Application\Contracts\AssignUserRoleServiceInterface::class));
    }

    // --- Service concrete classes exist ---

    public function test_create_role_service_class_exists(): void
    {
        $this->assertTrue(class_exists(CreateRoleService::class));
    }

    public function test_delete_role_service_class_exists(): void
    {
        $this->assertTrue(class_exists(DeleteRoleService::class));
    }

    public function test_sync_role_permissions_service_class_exists(): void
    {
        $this->assertTrue(class_exists(SyncRolePermissionsService::class));
    }

    public function test_create_permission_service_class_exists(): void
    {
        $this->assertTrue(class_exists(CreatePermissionService::class));
    }

    public function test_delete_permission_service_class_exists(): void
    {
        $this->assertTrue(class_exists(DeletePermissionService::class));
    }

    public function test_assign_user_role_service_class_exists(): void
    {
        $this->assertTrue(class_exists(AssignUserRoleService::class));
    }

    // --- Repository interface contracts ---

    public function test_permission_repository_interface_exists(): void
    {
        $this->assertTrue(interface_exists(PermissionRepositoryInterface::class));
    }

    public function test_role_repository_interface_exists(): void
    {
        $this->assertTrue(interface_exists(RoleRepositoryInterface::class));
    }

    public function test_user_role_repository_interface_exists(): void
    {
        $this->assertTrue(interface_exists(UserRoleRepositoryInterface::class));
    }
}
