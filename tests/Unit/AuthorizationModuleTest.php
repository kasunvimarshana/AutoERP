<?php
declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Modules\Authorization\Domain\Entities\Role;
use Modules\Authorization\Domain\Entities\Permission;
use Modules\Authorization\Domain\Exceptions\RoleNotFoundException;
use Modules\Authorization\Domain\Exceptions\PermissionNotFoundException;
use Modules\Authorization\Domain\RepositoryInterfaces\RoleRepositoryInterface;
use Modules\Authorization\Domain\RepositoryInterfaces\PermissionRepositoryInterface;
use Modules\Authorization\Domain\RepositoryInterfaces\UserRoleRepositoryInterface;
use Modules\Authorization\Application\Services\RoleService;
use Modules\Authorization\Application\Services\PermissionService;
use Modules\Authorization\Application\Services\UserRoleService;

class AuthorizationModuleTest extends TestCase
{
    private function makeRole(int $id = 1): Role
    {
        return new Role($id, 1, 'Admin', 'admin', 'Administrator role', new \DateTime(), new \DateTime());
    }

    private function makePermission(int $id = 1): Permission
    {
        return new Permission($id, 'View Users', 'users.view', 'user', 'Can view users', new \DateTime(), new \DateTime());
    }

    public function test_role_entity_getters(): void
    {
        $role = $this->makeRole();
        $this->assertEquals(1, $role->getId());
        $this->assertEquals(1, $role->getTenantId());
        $this->assertEquals('Admin', $role->getName());
        $this->assertEquals('admin', $role->getSlug());
        $this->assertEquals('Administrator role', $role->getDescription());
    }

    public function test_permission_entity_getters(): void
    {
        $permission = $this->makePermission();
        $this->assertEquals(1, $permission->getId());
        $this->assertEquals('View Users', $permission->getName());
        $this->assertEquals('users.view', $permission->getSlug());
        $this->assertEquals('user', $permission->getModule());
    }

    public function test_role_service_finds_role(): void
    {
        /** @var RoleRepositoryInterface&MockObject $repo */
        $repo = $this->createMock(RoleRepositoryInterface::class);
        $repo->expects($this->once())->method('findById')->with(1)->willReturn($this->makeRole());

        $service = new RoleService($repo);
        $result = $service->findById(1);
        $this->assertEquals(1, $result->getId());
    }

    public function test_role_service_throws_not_found(): void
    {
        /** @var RoleRepositoryInterface&MockObject $repo */
        $repo = $this->createMock(RoleRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $service = new RoleService($repo);
        $this->expectException(RoleNotFoundException::class);
        $service->findById(999);
    }

    public function test_permission_service_throws_not_found(): void
    {
        /** @var PermissionRepositoryInterface&MockObject $repo */
        $repo = $this->createMock(PermissionRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $service = new PermissionService($repo);
        $this->expectException(PermissionNotFoundException::class);
        $service->findById(999);
    }

    public function test_role_not_found_exception_message(): void
    {
        $e = new RoleNotFoundException(5);
        $this->assertStringContainsString('5', $e->getMessage());
        $this->assertStringContainsString('Role', $e->getMessage());
    }

    public function test_permission_not_found_exception_message(): void
    {
        $e = new PermissionNotFoundException(10);
        $this->assertStringContainsString('10', $e->getMessage());
        $this->assertStringContainsString('Permission', $e->getMessage());
    }

    // ──────────────────────────────────────────────────────────────────────
    // UserRoleService tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_user_role_service_get_user_roles(): void
    {
        /** @var UserRoleRepositoryInterface&MockObject $repo */
        $repo = $this->createMock(UserRoleRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('getUserRoles')
            ->with(5)
            ->willReturn(['admin', 'manager']);

        $service = new UserRoleService($repo);
        $result  = $service->getUserRoles(5);

        $this->assertEquals(['admin', 'manager'], $result);
    }

    public function test_user_role_service_user_has_permission(): void
    {
        $repo = $this->createMock(UserRoleRepositoryInterface::class);
        $repo->method('userHasPermission')->with(5, 'products.view')->willReturn(true);

        $service = new UserRoleService($repo);
        $this->assertTrue($service->userHasPermission(5, 'products.view'));
    }

    public function test_user_role_service_user_has_no_permission(): void
    {
        $repo = $this->createMock(UserRoleRepositoryInterface::class);
        $repo->method('userHasPermission')->with(5, 'products.delete')->willReturn(false);

        $service = new UserRoleService($repo);
        $this->assertFalse($service->userHasPermission(5, 'products.delete'));
    }

    public function test_user_role_service_user_has_role(): void
    {
        $repo = $this->createMock(UserRoleRepositoryInterface::class);
        $repo->method('userHasRole')->with(5, 'admin')->willReturn(true);

        $service = new UserRoleService($repo);
        $this->assertTrue($service->userHasRole(5, 'admin'));
    }

    public function test_user_role_service_assign_role(): void
    {
        $repo = $this->createMock(UserRoleRepositoryInterface::class);
        $repo->expects($this->once())->method('assignRole')->with(5, 3);

        $service = new UserRoleService($repo);
        $service->assignRole(5, 3);  // should not throw
        $this->assertTrue(true);  // reached here without exception
    }

    public function test_user_role_service_remove_role(): void
    {
        $repo = $this->createMock(UserRoleRepositoryInterface::class);
        $repo->expects($this->once())->method('removeRole')->with(5, 3);

        $service = new UserRoleService($repo);
        $service->removeRole(5, 3);
        $this->assertTrue(true);
    }

    public function test_user_role_service_sync_roles(): void
    {
        $repo = $this->createMock(UserRoleRepositoryInterface::class);
        $repo->expects($this->once())->method('syncRoles')->with(5, [1, 2, 3]);

        $service = new UserRoleService($repo);
        $service->syncRoles(5, [1, 2, 3]);
        $this->assertTrue(true);
    }

    // ──────────────────────────────────────────────────────────────────────
    // PermissionService additional tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_permission_service_finds_permission(): void
    {
        $repo = $this->createMock(PermissionRepositoryInterface::class);
        $repo->method('findById')->with(1)->willReturn($this->makePermission());

        $service = new PermissionService($repo);
        $result  = $service->findById(1);
        $this->assertEquals('users.view', $result->getSlug());
    }

    // ──────────────────────────────────────────────────────────────────────
    // Role entity – additional tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_role_with_no_description(): void
    {
        $role = new Role(2, 1, 'Viewer', 'viewer', null, new \DateTime(), new \DateTime());
        $this->assertNull($role->getDescription());
    }
}
