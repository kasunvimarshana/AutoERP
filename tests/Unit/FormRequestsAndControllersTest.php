<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modules\Tenant\Infrastructure\Http\Requests\StoreTenantRequest;
use Modules\Tenant\Infrastructure\Http\Requests\UpdateTenantRequest;
use Modules\Tenant\Infrastructure\Http\Requests\UpdateTenantConfigRequest;
use Modules\User\Infrastructure\Http\Requests\StoreUserRequest;
use Modules\User\Infrastructure\Http\Requests\UpdateUserRequest;
use Modules\User\Infrastructure\Http\Requests\StoreRoleRequest;
use Modules\User\Infrastructure\Http\Requests\SyncRolePermissionsRequest;
use Modules\User\Infrastructure\Http\Requests\StorePermissionRequest;
use Modules\OrganizationUnit\Infrastructure\Http\Requests\StoreOrganizationUnitRequest;
use Modules\OrganizationUnit\Infrastructure\Http\Requests\UpdateOrganizationUnitRequest;
use Modules\User\Infrastructure\Http\Controllers\RoleController;
use Modules\User\Infrastructure\Http\Controllers\PermissionController;
use Illuminate\Foundation\Http\FormRequest;

class FormRequestsAndControllersTest extends TestCase
{
    /**
     * Verify all dedicated FormRequest classes for Tenant module exist.
     */
    public function test_tenant_form_requests_exist(): void
    {
        $this->assertTrue(class_exists(StoreTenantRequest::class));
        $this->assertTrue(class_exists(UpdateTenantRequest::class));
        $this->assertTrue(class_exists(UpdateTenantConfigRequest::class));

        $this->assertInstanceOf(FormRequest::class, new StoreTenantRequest());
        $this->assertInstanceOf(FormRequest::class, new UpdateTenantRequest());
        $this->assertInstanceOf(FormRequest::class, new UpdateTenantConfigRequest());
    }

    /**
     * Verify all dedicated FormRequest classes for User module exist.
     */
    public function test_user_form_requests_exist(): void
    {
        $this->assertTrue(class_exists(StoreUserRequest::class));
        $this->assertTrue(class_exists(UpdateUserRequest::class));
        $this->assertTrue(class_exists(StoreRoleRequest::class));
        $this->assertTrue(class_exists(SyncRolePermissionsRequest::class));
        $this->assertTrue(class_exists(StorePermissionRequest::class));

        $this->assertInstanceOf(FormRequest::class, new StoreUserRequest());
        $this->assertInstanceOf(FormRequest::class, new UpdateUserRequest());
        $this->assertInstanceOf(FormRequest::class, new StoreRoleRequest());
        $this->assertInstanceOf(FormRequest::class, new SyncRolePermissionsRequest());
        $this->assertInstanceOf(FormRequest::class, new StorePermissionRequest());
    }

    /**
     * Verify all dedicated FormRequest classes for OrganizationUnit module exist.
     */
    public function test_organization_unit_form_requests_exist(): void
    {
        $this->assertTrue(class_exists(StoreOrganizationUnitRequest::class));
        $this->assertTrue(class_exists(UpdateOrganizationUnitRequest::class));

        $this->assertInstanceOf(FormRequest::class, new StoreOrganizationUnitRequest());
        $this->assertInstanceOf(FormRequest::class, new UpdateOrganizationUnitRequest());
    }

    /**
     * Verify RoleController and PermissionController exist.
     */
    public function test_role_and_permission_controllers_exist(): void
    {
        $this->assertTrue(class_exists(RoleController::class));
        $this->assertTrue(class_exists(PermissionController::class));
    }

    /**
     * Verify RoleController constructor injects interfaces.
     */
    public function test_role_controller_constructor_uses_interfaces(): void
    {
        $reflection = new \ReflectionClass(RoleController::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);

        $params = $constructor->getParameters();
        $paramTypes = array_map(fn($p) => $p->getType()?->getName(), $params);

        $this->assertContains(\Modules\User\Application\Contracts\CreateRoleServiceInterface::class, $paramTypes);
        $this->assertContains(\Modules\User\Application\Contracts\DeleteRoleServiceInterface::class, $paramTypes);
        $this->assertContains(\Modules\User\Application\Contracts\SyncRolePermissionsServiceInterface::class, $paramTypes);
        $this->assertContains(\Modules\User\Domain\RepositoryInterfaces\RoleRepositoryInterface::class, $paramTypes);
    }

    /**
     * Verify PermissionController constructor injects interfaces.
     */
    public function test_permission_controller_constructor_uses_interfaces(): void
    {
        $reflection = new \ReflectionClass(PermissionController::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);

        $params = $constructor->getParameters();
        $paramTypes = array_map(fn($p) => $p->getType()?->getName(), $params);

        $this->assertContains(\Modules\User\Application\Contracts\CreatePermissionServiceInterface::class, $paramTypes);
        $this->assertContains(\Modules\User\Application\Contracts\DeletePermissionServiceInterface::class, $paramTypes);
        $this->assertContains(\Modules\User\Domain\RepositoryInterfaces\PermissionRepositoryInterface::class, $paramTypes);
    }
}
