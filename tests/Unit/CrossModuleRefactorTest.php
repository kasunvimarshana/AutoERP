<?php

declare(strict_types=1);

namespace Tests\Unit;

// Auth
use Modules\Auth\Infrastructure\Http\Controllers\AuthController;
use Modules\Auth\Infrastructure\Providers\AuthModuleServiceProvider;

// Brand
use Modules\Brand\Application\Contracts\FindBrandLogosServiceInterface;
use Modules\Brand\Application\Contracts\FindBrandServiceInterface;
use Modules\Brand\Application\Services\FindBrandLogosService;
use Modules\Brand\Application\Services\FindBrandService;
use Modules\Brand\Infrastructure\Http\Controllers\BrandController;
use Modules\Brand\Infrastructure\Http\Controllers\BrandLogoController;
use Modules\Brand\Infrastructure\Http\Requests\UpdateBrandRequest;
use Modules\Brand\Infrastructure\Providers\BrandServiceProvider;

// Category
use Modules\Category\Application\Contracts\FindCategoryImagesServiceInterface;
use Modules\Category\Application\Contracts\FindCategoryServiceInterface;
use Modules\Category\Application\Services\FindCategoryImagesService;
use Modules\Category\Application\Services\FindCategoryService;
use Modules\Category\Infrastructure\Http\Controllers\CategoryController;
use Modules\Category\Infrastructure\Http\Controllers\CategoryImageController;
use Modules\Category\Infrastructure\Http\Requests\UpdateCategoryRequest;
use Modules\Category\Infrastructure\Providers\CategoryServiceProvider;

// Account
use Modules\Account\Infrastructure\Http\Requests\UpdateAccountRequest;

// Customer
use Modules\Customer\Infrastructure\Http\Requests\UpdateCustomerRequest;

// Supplier
use Modules\Supplier\Infrastructure\Http\Requests\UpdateSupplierRequest;

// Tenant
use Modules\Tenant\Application\Contracts\FindTenantServiceInterface;
use Modules\Tenant\Application\Services\FindTenantService;
use Modules\Tenant\Infrastructure\Http\Controllers\TenantController;
use Modules\Tenant\Infrastructure\Http\Requests\UpdateTenantRequest;
use Modules\Tenant\Infrastructure\Providers\TenantServiceProvider;

// User
use Modules\User\Application\Contracts\FindPermissionServiceInterface;
use Modules\User\Application\Contracts\FindRoleServiceInterface;
use Modules\User\Application\Contracts\FindUserAttachmentsServiceInterface;
use Modules\User\Application\Contracts\FindUserServiceInterface;
use Modules\User\Application\Services\FindPermissionService;
use Modules\User\Application\Services\FindRoleService;
use Modules\User\Application\Services\FindUserAttachmentsService;
use Modules\User\Application\Services\FindUserService;
use Modules\User\Infrastructure\Http\Controllers\PermissionController;
use Modules\User\Infrastructure\Http\Controllers\RoleController;
use Modules\User\Infrastructure\Http\Controllers\UserAttachmentController;
use Modules\User\Infrastructure\Http\Controllers\UserController;
use Modules\User\Infrastructure\Http\Requests\AssignRoleRequest;
use Modules\User\Infrastructure\Http\Requests\UpdateUserRequest;
use Modules\User\Infrastructure\Providers\UserServiceProvider;

use PHPUnit\Framework\TestCase;

/**
 * Cross-module refactor: update request `sometimes` guards,
 * DIP violations resolved (find service interfaces),
 * AssignRoleRequest, and service provider registrations.
 */
class CrossModuleRefactorTest extends TestCase
{
    // ── Update request `sometimes` guards ─────────────────────────────────────

    public function test_update_account_request_name_uses_sometimes(): void
    {
        $rules = (new UpdateAccountRequest)->rules();
        $this->assertStringContainsString('sometimes', $rules['name']);
    }

    public function test_update_account_request_code_uses_sometimes(): void
    {
        $rules = (new UpdateAccountRequest)->rules();
        $this->assertStringContainsString('sometimes', $rules['code']);
    }

    public function test_update_account_request_type_uses_sometimes(): void
    {
        $rules = (new UpdateAccountRequest)->rules();
        $this->assertStringContainsString('sometimes', $rules['type']);
    }

    public function test_update_brand_request_name_uses_sometimes(): void
    {
        $rules = (new UpdateBrandRequest)->rules();
        $this->assertStringContainsString('sometimes', $rules['name']);
    }

    public function test_update_category_request_name_uses_sometimes(): void
    {
        $rules = (new UpdateCategoryRequest)->rules();
        $this->assertStringContainsString('sometimes', $rules['name']);
    }

    public function test_update_customer_request_name_uses_sometimes(): void
    {
        $rules = (new UpdateCustomerRequest)->rules();
        $this->assertStringContainsString('sometimes', $rules['name']);
    }

    public function test_update_customer_request_code_uses_sometimes(): void
    {
        $rules = (new UpdateCustomerRequest)->rules();
        $this->assertStringContainsString('sometimes', $rules['code']);
    }

    public function test_update_supplier_request_name_uses_sometimes(): void
    {
        $rules = (new UpdateSupplierRequest)->rules();
        $this->assertStringContainsString('sometimes', $rules['name']);
    }

    public function test_update_supplier_request_code_uses_sometimes(): void
    {
        $rules = (new UpdateSupplierRequest)->rules();
        $this->assertStringContainsString('sometimes', $rules['code']);
    }

    public function test_update_tenant_request_name_uses_sometimes(): void
    {
        $rules = (new UpdateTenantRequest)->rules();
        $this->assertStringContainsString('sometimes', $rules['name']);
    }

    public function test_update_tenant_request_database_config_uses_sometimes(): void
    {
        $rules = (new UpdateTenantRequest)->rules();
        $this->assertStringContainsString('sometimes', $rules['database_config']);
    }

    public function test_update_user_request_email_uses_sometimes(): void
    {
        $rules = (new UpdateUserRequest)->rules();
        $this->assertStringContainsString('sometimes', $rules['email']);
    }

    public function test_update_user_request_first_name_uses_sometimes(): void
    {
        $rules = (new UpdateUserRequest)->rules();
        $this->assertStringContainsString('sometimes', $rules['first_name']);
    }

    public function test_update_user_request_last_name_uses_sometimes(): void
    {
        $rules = (new UpdateUserRequest)->rules();
        $this->assertStringContainsString('sometimes', $rules['last_name']);
    }

    public function test_update_user_request_does_not_contain_tenant_id(): void
    {
        $rules = (new UpdateUserRequest)->rules();
        $this->assertArrayNotHasKey('tenant_id', $rules, 'tenant_id must not be updatable');
    }

    // ── Brand: FindBrandLogosServiceInterface / Service ────────────────────────

    public function test_find_brand_logos_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(FindBrandLogosServiceInterface::class));
    }

    public function test_find_brand_logos_service_class_exists(): void
    {
        $this->assertTrue(class_exists(FindBrandLogosService::class));
    }

    public function test_find_brand_logos_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(FindBrandLogosService::class, FindBrandLogosServiceInterface::class)
        );
    }

    public function test_brand_logo_controller_injects_find_brand_logos_service(): void
    {
        $rc = new \ReflectionClass(BrandLogoController::class);
        $params = $rc->getConstructor()->getParameters();
        $types = array_map(fn ($p) => (string) $p->getType(), $params);
        $this->assertContains(FindBrandLogosServiceInterface::class, $types);
    }

    public function test_brand_logo_controller_does_not_inject_logo_repository(): void
    {
        $rc = new \ReflectionClass(BrandLogoController::class);
        $params = $rc->getConstructor()->getParameters();
        $types = array_map(fn ($p) => (string) $p->getType(), $params);
        $this->assertNotContains(
            'Modules\Brand\Domain\RepositoryInterfaces\BrandLogoRepositoryInterface',
            $types,
            'BrandLogoController must not inject the repository directly'
        );
    }

    public function test_brand_service_provider_registers_find_logos_service(): void
    {
        $rc = new \ReflectionClass(BrandServiceProvider::class);
        $src = file_get_contents($rc->getFileName());
        $this->assertStringContainsString(
            'FindBrandLogosServiceInterface::class',
            $src,
            'BrandServiceProvider must bind FindBrandLogosServiceInterface'
        );
    }

    // ── Category: FindCategoryServiceInterface / Service ──────────────────────

    public function test_find_category_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(FindCategoryServiceInterface::class));
    }

    public function test_find_category_service_class_exists(): void
    {
        $this->assertTrue(class_exists(FindCategoryService::class));
    }

    public function test_find_category_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(FindCategoryService::class, FindCategoryServiceInterface::class)
        );
    }

    public function test_category_controller_injects_find_category_service(): void
    {
        $rc = new \ReflectionClass(CategoryController::class);
        $params = $rc->getConstructor()->getParameters();
        $types = array_map(fn ($p) => (string) $p->getType(), $params);
        $this->assertContains(FindCategoryServiceInterface::class, $types);
    }

    public function test_category_controller_does_not_inject_category_repository(): void
    {
        $rc = new \ReflectionClass(CategoryController::class);
        $params = $rc->getConstructor()->getParameters();
        $types = array_map(fn ($p) => (string) $p->getType(), $params);
        $this->assertNotContains(
            'Modules\Category\Domain\RepositoryInterfaces\CategoryRepositoryInterface',
            $types,
            'CategoryController must not inject the repository directly'
        );
    }

    // ── Category: FindCategoryImagesServiceInterface / Service ─────────────────

    public function test_find_category_images_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(FindCategoryImagesServiceInterface::class));
    }

    public function test_find_category_images_service_class_exists(): void
    {
        $this->assertTrue(class_exists(FindCategoryImagesService::class));
    }

    public function test_find_category_images_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(FindCategoryImagesService::class, FindCategoryImagesServiceInterface::class)
        );
    }

    public function test_category_image_controller_injects_find_category_images_service(): void
    {
        $rc = new \ReflectionClass(CategoryImageController::class);
        $params = $rc->getConstructor()->getParameters();
        $types = array_map(fn ($p) => (string) $p->getType(), $params);
        $this->assertContains(FindCategoryImagesServiceInterface::class, $types);
    }

    public function test_category_image_controller_does_not_inject_category_image_repository(): void
    {
        $rc = new \ReflectionClass(CategoryImageController::class);
        $params = $rc->getConstructor()->getParameters();
        $types = array_map(fn ($p) => (string) $p->getType(), $params);
        $this->assertNotContains(
            'Modules\Category\Domain\RepositoryInterfaces\CategoryImageRepositoryInterface',
            $types,
            'CategoryImageController must not inject the repository directly'
        );
    }

    public function test_category_service_provider_registers_find_category_service(): void
    {
        $rc = new \ReflectionClass(CategoryServiceProvider::class);
        $src = file_get_contents($rc->getFileName());
        $this->assertStringContainsString(
            'FindCategoryServiceInterface::class',
            $src,
            'CategoryServiceProvider must bind FindCategoryServiceInterface'
        );
    }

    public function test_category_service_provider_registers_find_category_images_service(): void
    {
        $rc = new \ReflectionClass(CategoryServiceProvider::class);
        $src = file_get_contents($rc->getFileName());
        $this->assertStringContainsString(
            'FindCategoryImagesServiceInterface::class',
            $src,
            'CategoryServiceProvider must bind FindCategoryImagesServiceInterface'
        );
    }

    // ── Tenant: FindTenantServiceInterface / Service ───────────────────────────

    public function test_find_tenant_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(FindTenantServiceInterface::class));
    }

    public function test_find_tenant_service_class_exists(): void
    {
        $this->assertTrue(class_exists(FindTenantService::class));
    }

    public function test_find_tenant_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(FindTenantService::class, FindTenantServiceInterface::class)
        );
    }

    public function test_find_tenant_service_interface_declares_find_by_domain(): void
    {
        $rc = new \ReflectionClass(FindTenantServiceInterface::class);
        $this->assertTrue($rc->hasMethod('findByDomain'));
    }

    public function test_tenant_controller_injects_find_tenant_service(): void
    {
        $rc = new \ReflectionClass(TenantController::class);
        $params = $rc->getConstructor()->getParameters();
        $types = array_map(fn ($p) => (string) $p->getType(), $params);
        $this->assertContains(FindTenantServiceInterface::class, $types);
    }

    public function test_tenant_controller_does_not_inject_tenant_repository(): void
    {
        $rc = new \ReflectionClass(TenantController::class);
        $params = $rc->getConstructor()->getParameters();
        $types = array_map(fn ($p) => (string) $p->getType(), $params);
        $this->assertNotContains(
            'Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface',
            $types,
            'TenantController must not inject the repository directly'
        );
    }

    public function test_tenant_service_provider_registers_find_tenant_service(): void
    {
        $rc = new \ReflectionClass(TenantServiceProvider::class);
        $src = file_get_contents($rc->getFileName());
        $this->assertStringContainsString(
            'FindTenantServiceInterface::class',
            $src,
            'TenantServiceProvider must bind FindTenantServiceInterface'
        );
    }

    // ── User: FindUserAttachmentsServiceInterface / Service ───────────────────

    public function test_find_user_attachments_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(FindUserAttachmentsServiceInterface::class));
    }

    public function test_find_user_attachments_service_class_exists(): void
    {
        $this->assertTrue(class_exists(FindUserAttachmentsService::class));
    }

    public function test_find_user_attachments_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(FindUserAttachmentsService::class, FindUserAttachmentsServiceInterface::class)
        );
    }

    public function test_user_attachment_controller_injects_find_user_attachments_service(): void
    {
        $rc = new \ReflectionClass(UserAttachmentController::class);
        $params = $rc->getConstructor()->getParameters();
        $types = array_map(fn ($p) => (string) $p->getType(), $params);
        $this->assertContains(FindUserAttachmentsServiceInterface::class, $types);
    }

    public function test_user_attachment_controller_does_not_inject_attachment_repository(): void
    {
        $rc = new \ReflectionClass(UserAttachmentController::class);
        $params = $rc->getConstructor()->getParameters();
        $types = array_map(fn ($p) => (string) $p->getType(), $params);
        $this->assertNotContains(
            'Modules\User\Domain\RepositoryInterfaces\UserAttachmentRepositoryInterface',
            $types,
            'UserAttachmentController must not inject the repository directly'
        );
    }

    public function test_user_service_provider_registers_find_user_attachments_service(): void
    {
        $rc = new \ReflectionClass(UserServiceProvider::class);
        $src = file_get_contents($rc->getFileName());
        $this->assertStringContainsString(
            'FindUserAttachmentsServiceInterface::class',
            $src,
            'UserServiceProvider must bind FindUserAttachmentsServiceInterface'
        );
    }

    // ── User: AssignRoleRequest replaces inline validation ────────────────────

    public function test_assign_role_request_class_exists(): void
    {
        $this->assertTrue(class_exists(AssignRoleRequest::class));
    }

    public function test_assign_role_request_has_role_id_rule(): void
    {
        $rules = (new AssignRoleRequest)->rules();
        $this->assertArrayHasKey('role_id', $rules);
        $this->assertStringContainsString('required', $rules['role_id']);
        $this->assertStringContainsString('integer', $rules['role_id']);
    }

    public function test_user_controller_assign_role_uses_request_class(): void
    {
        $rc = new \ReflectionClass(UserController::class);
        $method = $rc->getMethod('assignRole');
        $params = $method->getParameters();
        $firstParamType = (string) $params[0]->getType();
        $this->assertSame(
            AssignRoleRequest::class,
            $firstParamType,
            'assignRole() must accept AssignRoleRequest, not the generic Request'
        );
    }

    // ── Auth: DIP fix — AuthController uses FindUserServiceInterface ──────────

    public function test_auth_controller_injects_find_user_service_interface(): void
    {
        $rc = new \ReflectionClass(AuthController::class);
        $types = array_map(fn ($p) => (string) $p->getType(), $rc->getConstructor()->getParameters());
        $this->assertContains(
            FindUserServiceInterface::class,
            $types,
            'AuthController must inject FindUserServiceInterface (not the raw repository) for the me() endpoint.'
        );
    }

    public function test_auth_controller_does_not_inject_user_repository_interface(): void
    {
        $rc = new \ReflectionClass(AuthController::class);
        $types = array_map(fn ($p) => (string) $p->getType(), $rc->getConstructor()->getParameters());
        $this->assertNotContains(
            'Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface',
            $types,
            'AuthController must not inject UserRepositoryInterface directly (DIP violation).'
        );
    }

    public function test_auth_module_service_provider_does_not_bind_user_repository_for_controller(): void
    {
        $rc = new \ReflectionClass(AuthModuleServiceProvider::class);
        $src = file_get_contents($rc->getFileName());
        $this->assertStringNotContainsString(
            'Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface',
            $src,
            'AuthModuleServiceProvider must not reference UserRepositoryInterface from User domain.'
        );
    }
}
