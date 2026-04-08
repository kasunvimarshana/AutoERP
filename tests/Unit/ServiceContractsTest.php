<?php

namespace Tests\Unit;

use Modules\Core\Application\Contracts\FileStorageServiceInterface;
// Tenant service interfaces
use Modules\Core\Application\Contracts\ReadServiceInterface;
use Modules\Core\Application\Contracts\ServiceInterface;
use Modules\Core\Application\Contracts\WriteServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\CreateOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitAttachmentServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitServiceInterface;
// User service interfaces
use Modules\OrganizationUnit\Application\Contracts\MoveOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\UpdateOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\UploadOrganizationUnitAttachmentServiceInterface;
// Product service interfaces
use Modules\Product\Application\Contracts\CreateProductServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductImageServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductServiceInterface;
use Modules\Product\Application\Contracts\UploadProductImageServiceInterface;
use Modules\Tenant\Application\Contracts\CreateTenantServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantAttachmentServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantServiceInterface;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
// OrganizationUnit service interfaces
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantConfigServiceInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantServiceInterface;
use Modules\Tenant\Application\Contracts\UploadTenantAttachmentServiceInterface;
use Modules\Tenant\Application\Services\TenantConfigManager;
use Modules\Tenant\Domain\Contracts\TenantConfigInterface;
// Core contracts
use Modules\Tenant\Infrastructure\Http\Middleware\ResolveTenant;
use Modules\Tenant\Infrastructure\Services\TenantConfig;
use Modules\Tenant\Infrastructure\Services\TenantConfigClient;
use Modules\User\Application\Contracts\AssignRoleServiceInterface;
// Tenant infrastructure
use Modules\User\Application\Contracts\CreateUserServiceInterface;
use Modules\User\Application\Contracts\DeleteUserAttachmentServiceInterface;
use Modules\User\Application\Contracts\DeleteUserServiceInterface;
use Modules\User\Application\Contracts\UpdatePreferencesServiceInterface;
use Modules\User\Application\Contracts\UpdateUserServiceInterface;
use Modules\User\Application\Contracts\UploadUserAttachmentServiceInterface;
use PHPUnit\Framework\TestCase;

class ServiceContractsTest extends TestCase
{
    /**
     * Verify all Tenant service interface contracts exist.
     */
    public function test_all_tenant_service_interfaces_exist(): void
    {
        $this->assertTrue(interface_exists(CreateTenantServiceInterface::class));
        $this->assertTrue(interface_exists(UpdateTenantServiceInterface::class));
        $this->assertTrue(interface_exists(DeleteTenantServiceInterface::class));
        $this->assertTrue(interface_exists(UpdateTenantConfigServiceInterface::class));
        $this->assertTrue(interface_exists(UploadTenantAttachmentServiceInterface::class));
        $this->assertTrue(interface_exists(DeleteTenantAttachmentServiceInterface::class));
    }

    /**
     * Verify all User service interface contracts exist.
     */
    public function test_all_user_service_interfaces_exist(): void
    {
        $this->assertTrue(interface_exists(CreateUserServiceInterface::class));
        $this->assertTrue(interface_exists(UpdateUserServiceInterface::class));
        $this->assertTrue(interface_exists(DeleteUserServiceInterface::class));
        $this->assertTrue(interface_exists(AssignRoleServiceInterface::class));
        $this->assertTrue(interface_exists(UpdatePreferencesServiceInterface::class));
        $this->assertTrue(interface_exists(UploadUserAttachmentServiceInterface::class));
        $this->assertTrue(interface_exists(DeleteUserAttachmentServiceInterface::class));
    }

    /**
     * Verify all OrganizationUnit service interface contracts exist.
     */
    public function test_all_organization_unit_service_interfaces_exist(): void
    {
        $this->assertTrue(interface_exists(CreateOrganizationUnitServiceInterface::class));
        $this->assertTrue(interface_exists(UpdateOrganizationUnitServiceInterface::class));
        $this->assertTrue(interface_exists(DeleteOrganizationUnitServiceInterface::class));
        $this->assertTrue(interface_exists(MoveOrganizationUnitServiceInterface::class));
        $this->assertTrue(interface_exists(UploadOrganizationUnitAttachmentServiceInterface::class));
        $this->assertTrue(interface_exists(DeleteOrganizationUnitAttachmentServiceInterface::class));
    }

    /**
     * Verify all Product service interface contracts exist.
     */
    public function test_all_product_service_interfaces_exist(): void
    {
        $this->assertTrue(interface_exists(CreateProductServiceInterface::class));
        $this->assertTrue(interface_exists(UpdateProductServiceInterface::class));
        $this->assertTrue(interface_exists(DeleteProductServiceInterface::class));
        $this->assertTrue(interface_exists(UploadProductImageServiceInterface::class));
        $this->assertTrue(interface_exists(DeleteProductImageServiceInterface::class));
    }

    /**
     * Verify the Tenant ResolveTenant middleware exists (not the removed Core one).
     */
    public function test_tenant_resolve_tenant_middleware_exists(): void
    {
        $this->assertTrue(class_exists(ResolveTenant::class));
    }

    /**
     * Verify the Core ResolveTenant middleware file no longer exists.
     */
    public function test_core_resolve_tenant_middleware_removed(): void
    {
        $path = dirname(__DIR__, 2)
            .'/app/Modules/Core/Infrastructure/Http/Middleware/ResolveTenant.php';
        $this->assertFalse(file_exists($path), 'Core ResolveTenant middleware should have been removed.');
    }

    /**
     * Verify TenantConfigInterface and its implementation exist.
     */
    public function test_tenant_config_interface_and_implementation_exist(): void
    {
        $this->assertTrue(interface_exists(TenantConfigInterface::class));
        $this->assertTrue(class_exists(TenantConfig::class));
        $this->assertTrue(is_subclass_of(TenantConfig::class, TenantConfigInterface::class));
    }

    /**
     * Verify the unused TenantConfigRepositoryInterface file was removed.
     */
    public function test_tenant_config_repository_interface_removed(): void
    {
        $path = dirname(__DIR__, 2)
            .'/app/Modules/Tenant/Domain/Contracts/TenantConfigRepositoryInterface.php';
        $this->assertFalse(file_exists($path), 'TenantConfigRepositoryInterface should have been removed.');
    }

    /**
     * Verify FileStorageServiceInterface exists in the correct Contracts namespace (not Services).
     */
    public function test_file_storage_service_interface_in_correct_namespace(): void
    {
        $this->assertTrue(interface_exists(FileStorageServiceInterface::class));

        $path = dirname(__DIR__, 2)
            .'/app/Modules/Core/Application/Contracts/FileStorageServiceInterface.php';
        $this->assertTrue(file_exists($path), 'FileStorageServiceInterface must be in Application/Contracts/.');

        $wrongPath = dirname(__DIR__, 2)
            .'/app/Modules/Core/Application/Services/FileStorageServiceInterface.php';
        $this->assertFalse(file_exists($wrongPath), 'FileStorageServiceInterface must NOT remain in Application/Services/.');
    }

    /**
     * Verify the ReadServiceInterface exists and ServiceInterface correctly extends it.
     */
    public function test_read_service_interface_exists_and_service_interface_extends_it(): void
    {
        $this->assertTrue(interface_exists(ReadServiceInterface::class));
        $this->assertTrue(interface_exists(WriteServiceInterface::class));
        $this->assertTrue(interface_exists(ServiceInterface::class));

        // ServiceInterface must extend both ReadServiceInterface and WriteServiceInterface
        $this->assertTrue(
            is_subclass_of(ServiceInterface::class, ReadServiceInterface::class, true),
            'ServiceInterface must extend ReadServiceInterface.'
        );
        $this->assertTrue(
            is_subclass_of(ServiceInterface::class, WriteServiceInterface::class, true),
            'ServiceInterface must extend WriteServiceInterface.'
        );
    }

    /**
     * Verify TenantConfigClientInterface and TenantConfigManagerInterface exist
     * and their implementations correctly implement them.
     */
    public function test_tenant_config_client_and_manager_interfaces_exist(): void
    {
        $this->assertTrue(interface_exists(TenantConfigClientInterface::class));
        $this->assertTrue(interface_exists(TenantConfigManagerInterface::class));

        $this->assertTrue(class_exists(TenantConfigClient::class));
        $this->assertTrue(class_exists(TenantConfigManager::class));

        $this->assertTrue(
            is_subclass_of(TenantConfigClient::class, TenantConfigClientInterface::class),
            'TenantConfigClient must implement TenantConfigClientInterface.'
        );
        $this->assertTrue(
            is_subclass_of(TenantConfigManager::class, TenantConfigManagerInterface::class),
            'TenantConfigManager must implement TenantConfigManagerInterface.'
        );
    }

    /**
     * Verify the ResolveTenant middleware constructor requires interfaces (not concrete classes).
     */
    public function test_resolve_tenant_middleware_constructor_uses_interfaces(): void
    {
        $reflection = new \ReflectionClass(ResolveTenant::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);

        $params = $constructor->getParameters();
        $this->assertCount(2, $params);

        $clientType = $params[0]->getType()?->getName();
        $managerType = $params[1]->getType()?->getName();

        $this->assertSame(TenantConfigClientInterface::class, $clientType,
            'ResolveTenant must inject TenantConfigClientInterface, not a concrete class.');
        $this->assertSame(TenantConfigManagerInterface::class, $managerType,
            'ResolveTenant must inject TenantConfigManagerInterface, not a concrete class.');
    }
}


