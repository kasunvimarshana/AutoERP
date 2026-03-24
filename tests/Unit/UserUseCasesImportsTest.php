<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modules\User\Application\UseCases\CreateUser;
use Modules\User\Application\UseCases\GetUser;
use Modules\User\Application\UseCases\ListUsers;
use Modules\User\Application\UseCases\UpdateUser;
use Modules\User\Application\UseCases\DeleteUser;
use Modules\User\Application\UseCases\AssignRole;
use Modules\User\Application\UseCases\UpdatePreferences;
use Modules\User\Domain\Events\UserCreated;
use Modules\User\Domain\Events\UserUpdated;
use Modules\User\Domain\Events\RoleAssigned;
use Modules\User\Application\Contracts\CreateRoleServiceInterface;
use Modules\User\Application\Contracts\DeleteRoleServiceInterface;
use Modules\User\Application\Contracts\SyncRolePermissionsServiceInterface;
use Modules\User\Application\Contracts\CreatePermissionServiceInterface;
use Modules\User\Application\Contracts\DeletePermissionServiceInterface;
use Modules\User\Application\Services\CreateRoleService;
use Modules\User\Application\Services\DeleteRoleService;
use Modules\User\Application\Services\SyncRolePermissionsService;
use Modules\User\Application\Services\CreatePermissionService;
use Modules\User\Application\Services\DeletePermissionService;

class UserUseCasesImportsTest extends TestCase
{
    /**
     * Verify that all User use case classes can be loaded without fatal errors.
     */
    public function test_all_user_use_case_classes_exist(): void
    {
        $this->assertTrue(class_exists(CreateUser::class));
        $this->assertTrue(class_exists(GetUser::class));
        $this->assertTrue(class_exists(ListUsers::class));
        $this->assertTrue(class_exists(UpdateUser::class));
        $this->assertTrue(class_exists(DeleteUser::class));
        $this->assertTrue(class_exists(AssignRole::class));
        $this->assertTrue(class_exists(UpdatePreferences::class));
    }

    public function test_all_user_event_classes_exist(): void
    {
        $this->assertTrue(class_exists(UserCreated::class));
        $this->assertTrue(class_exists(UserUpdated::class));
        $this->assertTrue(class_exists(RoleAssigned::class));
    }

    public function test_all_role_and_permission_service_interfaces_exist(): void
    {
        $this->assertTrue(interface_exists(CreateRoleServiceInterface::class));
        $this->assertTrue(interface_exists(DeleteRoleServiceInterface::class));
        $this->assertTrue(interface_exists(SyncRolePermissionsServiceInterface::class));
        $this->assertTrue(interface_exists(CreatePermissionServiceInterface::class));
        $this->assertTrue(interface_exists(DeletePermissionServiceInterface::class));
    }

    public function test_all_role_and_permission_service_implementations_exist(): void
    {
        $this->assertTrue(class_exists(CreateRoleService::class));
        $this->assertTrue(class_exists(DeleteRoleService::class));
        $this->assertTrue(class_exists(SyncRolePermissionsService::class));
        $this->assertTrue(class_exists(CreatePermissionService::class));
        $this->assertTrue(class_exists(DeletePermissionService::class));
    }

    public function test_role_service_implementations_implement_their_interfaces(): void
    {
        $this->assertTrue(
            is_subclass_of(CreateRoleService::class, CreateRoleServiceInterface::class),
            'CreateRoleService must implement CreateRoleServiceInterface.'
        );
        $this->assertTrue(
            is_subclass_of(DeleteRoleService::class, DeleteRoleServiceInterface::class),
            'DeleteRoleService must implement DeleteRoleServiceInterface.'
        );
        $this->assertTrue(
            is_subclass_of(SyncRolePermissionsService::class, SyncRolePermissionsServiceInterface::class),
            'SyncRolePermissionsService must implement SyncRolePermissionsServiceInterface.'
        );
        $this->assertTrue(
            is_subclass_of(CreatePermissionService::class, CreatePermissionServiceInterface::class),
            'CreatePermissionService must implement CreatePermissionServiceInterface.'
        );
        $this->assertTrue(
            is_subclass_of(DeletePermissionService::class, DeletePermissionServiceInterface::class),
            'DeletePermissionService must implement DeletePermissionServiceInterface.'
        );
    }
}
