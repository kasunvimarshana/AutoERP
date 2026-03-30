<?php

namespace Tests\Unit;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Modules\Core\Domain\ValueObjects\DatabaseConfig;
use Modules\Tenant\Application\Contracts\AttachmentStorageStrategyInterface;
use Modules\Tenant\Application\Contracts\BulkUploadTenantAttachmentsServiceInterface;
use Modules\Tenant\Application\Contracts\CreateTenantServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantAttachmentServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantServiceInterface;
use Modules\Tenant\Application\Contracts\FindTenantAttachmentsServiceInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantConfigServiceInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantServiceInterface;
use Modules\Tenant\Application\Contracts\UploadTenantAttachmentServiceInterface;
use Modules\Tenant\Application\Services\BulkUploadTenantAttachmentsService;
use Modules\Tenant\Application\Services\DeleteTenantAttachmentService;
use Modules\Tenant\Application\Services\FindTenantAttachmentsService;
use Modules\Tenant\Application\Services\UploadTenantAttachmentService;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\Entities\TenantAttachment;
use Modules\Tenant\Domain\Exceptions\AttachmentNotFoundException;
use Modules\Tenant\Domain\Exceptions\TenantNotFoundException;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantAttachmentRepositoryInterface;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;
use Modules\Tenant\Infrastructure\Http\Controllers\TenantAttachmentController;
use Modules\Tenant\Infrastructure\Http\Controllers\TenantController;
use Modules\Tenant\Infrastructure\Http\Requests\StoreTenantRequest;
use Modules\Tenant\Infrastructure\Http\Requests\UpdateTenantRequest;
use Modules\Tenant\Infrastructure\Http\Requests\UploadTenantAttachmentRequest;
use Modules\Tenant\Infrastructure\Http\Resources\TenantAttachmentResource;
use Modules\Tenant\Infrastructure\Providers\TenantServiceProvider;
use Modules\Tenant\Infrastructure\Storage\DefaultAttachmentStorageStrategy;
use PHPUnit\Framework\TestCase;

class TenantModuleTest extends TestCase
{
    // ── AttachmentStorageStrategyInterface ────────────────────────────────────

    public function test_attachment_storage_strategy_interface_exists(): void
    {
        $this->assertTrue(interface_exists(AttachmentStorageStrategyInterface::class));
    }

    public function test_attachment_storage_strategy_interface_declares_required_methods(): void
    {
        $rc = new \ReflectionClass(AttachmentStorageStrategyInterface::class);

        $this->assertTrue($rc->hasMethod('store'));
        $this->assertTrue($rc->hasMethod('storeFromPath'));
        $this->assertTrue($rc->hasMethod('delete'));
        $this->assertTrue($rc->hasMethod('stream'));
    }

    // ── DefaultAttachmentStorageStrategy ─────────────────────────────────────

    public function test_default_attachment_storage_strategy_class_exists(): void
    {
        $this->assertTrue(class_exists(DefaultAttachmentStorageStrategy::class));
    }

    public function test_default_attachment_storage_strategy_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(
                DefaultAttachmentStorageStrategy::class,
                AttachmentStorageStrategyInterface::class
            ),
            'DefaultAttachmentStorageStrategy must implement AttachmentStorageStrategyInterface.'
        );
    }

    public function test_default_attachment_storage_strategy_store_delegates_to_file_storage(): void
    {
        $fileStorage = $this->createMock(\Modules\Core\Application\Contracts\FileStorageServiceInterface::class);
        $file        = $this->createMock(UploadedFile::class);

        $fileStorage->expects($this->once())
            ->method('storeFile')
            ->with($file, 'tenants/42')
            ->willReturn('tenants/42/logo.png');

        $strategy = new DefaultAttachmentStorageStrategy($fileStorage);
        $path     = $strategy->store($file, 42);

        $this->assertSame('tenants/42/logo.png', $path);
    }

    public function test_default_attachment_storage_strategy_delete_delegates_to_file_storage(): void
    {
        $fileStorage = $this->createMock(\Modules\Core\Application\Contracts\FileStorageServiceInterface::class);
        $fileStorage->expects($this->once())
            ->method('delete')
            ->with('tenants/42/logo.png')
            ->willReturn(true);

        $strategy = new DefaultAttachmentStorageStrategy($fileStorage);
        $this->assertTrue($strategy->delete('tenants/42/logo.png'));
    }

    // ── FindTenantAttachmentsServiceInterface ─────────────────────────────────

    public function test_find_tenant_attachments_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(FindTenantAttachmentsServiceInterface::class));
    }

    public function test_find_tenant_attachments_service_interface_declares_required_methods(): void
    {
        $rc = new \ReflectionClass(FindTenantAttachmentsServiceInterface::class);

        $this->assertTrue($rc->hasMethod('findByTenant'));
        $this->assertTrue($rc->hasMethod('findByUuid'));
        $this->assertTrue($rc->hasMethod('find'));
    }

    // ── FindTenantAttachmentsService ─────────────────────────────────────────

    public function test_find_tenant_attachments_service_class_exists(): void
    {
        $this->assertTrue(class_exists(FindTenantAttachmentsService::class));
    }

    public function test_find_tenant_attachments_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(FindTenantAttachmentsService::class, FindTenantAttachmentsServiceInterface::class),
            'FindTenantAttachmentsService must implement FindTenantAttachmentsServiceInterface.'
        );
    }

    public function test_find_tenant_attachments_service_find_by_tenant_delegates_to_repository(): void
    {
        $repo = $this->createMock(TenantAttachmentRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('getByTenant')
            ->with(7, 'logo')
            ->willReturn(new Collection);

        $service = new FindTenantAttachmentsService($repo);
        $result  = $service->findByTenant(7, 'logo');

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_find_tenant_attachments_service_find_by_uuid_delegates_to_repository(): void
    {
        $uuid       = 'test-uuid-1234';
        $attachment = $this->createMock(TenantAttachment::class);

        $repo = $this->createMock(TenantAttachmentRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('findByUuid')
            ->with($uuid)
            ->willReturn($attachment);

        $service = new FindTenantAttachmentsService($repo);
        $this->assertSame($attachment, $service->findByUuid($uuid));
    }

    public function test_find_tenant_attachments_service_find_returns_null_when_missing(): void
    {
        $repo = $this->createMock(TenantAttachmentRepositoryInterface::class);
        $repo->method('find')->willReturn(null);

        $service = new FindTenantAttachmentsService($repo);
        $this->assertNull($service->find(9999));
    }

    // ── BulkUploadTenantAttachmentsServiceInterface ───────────────────────────

    public function test_bulk_upload_tenant_attachments_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(BulkUploadTenantAttachmentsServiceInterface::class));
    }

    // ── BulkUploadTenantAttachmentsService ────────────────────────────────────

    public function test_bulk_upload_tenant_attachments_service_class_exists(): void
    {
        $this->assertTrue(class_exists(BulkUploadTenantAttachmentsService::class));
    }

    public function test_bulk_upload_tenant_attachments_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(
                BulkUploadTenantAttachmentsService::class,
                BulkUploadTenantAttachmentsServiceInterface::class
            ),
            'BulkUploadTenantAttachmentsService must implement BulkUploadTenantAttachmentsServiceInterface.'
        );
    }

    public function test_bulk_upload_service_constructor_accepts_storage_strategy(): void
    {
        $tenantRepo    = $this->createMock(TenantRepositoryInterface::class);
        $attachRepo    = $this->createMock(TenantAttachmentRepositoryInterface::class);
        $strategy      = $this->createMock(AttachmentStorageStrategyInterface::class);

        $service = new BulkUploadTenantAttachmentsService($tenantRepo, $attachRepo, $strategy);

        $this->assertInstanceOf(BulkUploadTenantAttachmentsServiceInterface::class, $service);
    }

    public function test_bulk_upload_service_has_correct_dependencies(): void
    {
        $rc = new \ReflectionClass(BulkUploadTenantAttachmentsService::class);

        $paramTypes = [];
        foreach ($rc->getConstructor()->getParameters() as $param) {
            $paramTypes[] = $param->getType() ? $param->getType()->getName() : null;
        }

        $this->assertContains(TenantRepositoryInterface::class, $paramTypes);
        $this->assertContains(TenantAttachmentRepositoryInterface::class, $paramTypes);
        $this->assertContains(AttachmentStorageStrategyInterface::class, $paramTypes);
    }

    // ── UploadTenantAttachmentService ─────────────────────────────────────────

    public function test_upload_tenant_attachment_service_uses_storage_strategy(): void
    {
        $rc = new \ReflectionClass(UploadTenantAttachmentService::class);

        $strategyParam = null;
        foreach ($rc->getConstructor()->getParameters() as $param) {
            if ($param->getType() && $param->getType()->getName() === AttachmentStorageStrategyInterface::class) {
                $strategyParam = $param;
                break;
            }
        }

        $this->assertNotNull(
            $strategyParam,
            'UploadTenantAttachmentService constructor must accept AttachmentStorageStrategyInterface.'
        );
    }

    public function test_upload_tenant_attachment_service_no_longer_uses_file_storage_service_directly(): void
    {
        $rc = new \ReflectionClass(UploadTenantAttachmentService::class);

        foreach ($rc->getConstructor()->getParameters() as $param) {
            $type = $param->getType() ? $param->getType()->getName() : null;
            $this->assertNotSame(
                \Modules\Core\Application\Contracts\FileStorageServiceInterface::class,
                $type,
                'UploadTenantAttachmentService must not directly depend on FileStorageServiceInterface.'
            );
        }
    }

    public function test_upload_tenant_attachment_service_throws_when_tenant_not_found(): void
    {
        $this->expectException(TenantNotFoundException::class);

        $tenantRepo = $this->createMock(TenantRepositoryInterface::class);
        $tenantRepo->method('find')->willReturn(null);

        $attachRepo = $this->createMock(TenantAttachmentRepositoryInterface::class);
        $strategy   = $this->createMock(AttachmentStorageStrategyInterface::class);

        $service = new UploadTenantAttachmentService($tenantRepo, $attachRepo, $strategy);

        $file = $this->createMock(UploadedFile::class);
        $file->method('getClientOriginalName')->willReturn('test.pdf');
        $file->method('getMimeType')->willReturn('application/pdf');
        $file->method('getSize')->willReturn(1024);

        // Use Reflection to call protected handle() directly (avoids DB::transaction)
        $method = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, ['tenant_id' => 99, 'file' => $file]);
    }

    public function test_upload_tenant_attachment_service_stores_file_via_strategy(): void
    {
        $tenant = $this->createTestTenant(1);

        $tenantRepo = $this->createMock(TenantRepositoryInterface::class);
        $tenantRepo->method('find')->willReturn($tenant);

        $savedAttachment = $this->createTestAttachment(1, 1);

        $attachRepo = $this->createMock(TenantAttachmentRepositoryInterface::class);
        $attachRepo->expects($this->once())
            ->method('save')
            ->willReturn($savedAttachment);

        $strategy = $this->createMock(AttachmentStorageStrategyInterface::class);
        $strategy->expects($this->once())
            ->method('store')
            ->willReturn('tenants/1/file.pdf');

        $file = $this->createMock(UploadedFile::class);
        $file->method('getClientOriginalName')->willReturn('file.pdf');
        $file->method('getMimeType')->willReturn('application/pdf');
        $file->method('getSize')->willReturn(2048);

        $service = new UploadTenantAttachmentService($tenantRepo, $attachRepo, $strategy);
        $method  = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $result = $method->invoke($service, ['tenant_id' => 1, 'file' => $file]);

        $this->assertInstanceOf(TenantAttachment::class, $result);
    }

    public function test_upload_tenant_attachment_service_updates_logo_path_on_logo_type(): void
    {
        $tenant = $this->createTestTenant(1);

        $tenantRepo = $this->createMock(TenantRepositoryInterface::class);
        $tenantRepo->method('find')->willReturn($tenant);
        // save() must be called twice: once for attachment, once for logo_path update
        $tenantRepo->expects($this->once())->method('save')->willReturn($tenant);

        $savedAttachment = $this->createTestAttachment(1, 1);
        $attachRepo      = $this->createMock(TenantAttachmentRepositoryInterface::class);
        $attachRepo->method('save')->willReturn($savedAttachment);

        $strategy = $this->createMock(AttachmentStorageStrategyInterface::class);
        $strategy->method('store')->willReturn('tenants/1/logo.png');

        $file = $this->createMock(UploadedFile::class);
        $file->method('getClientOriginalName')->willReturn('logo.png');
        $file->method('getMimeType')->willReturn('image/png');
        $file->method('getSize')->willReturn(1024);

        $service = new UploadTenantAttachmentService($tenantRepo, $attachRepo, $strategy);
        $method  = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, ['tenant_id' => 1, 'file' => $file, 'type' => 'logo']);
    }

    // ── DeleteTenantAttachmentService ─────────────────────────────────────────

    public function test_delete_tenant_attachment_service_uses_storage_strategy(): void
    {
        $rc = new \ReflectionClass(DeleteTenantAttachmentService::class);

        $strategyParam = null;
        foreach ($rc->getConstructor()->getParameters() as $param) {
            if ($param->getType() && $param->getType()->getName() === AttachmentStorageStrategyInterface::class) {
                $strategyParam = $param;
                break;
            }
        }

        $this->assertNotNull(
            $strategyParam,
            'DeleteTenantAttachmentService constructor must accept AttachmentStorageStrategyInterface.'
        );
    }

    public function test_delete_tenant_attachment_service_throws_when_not_found(): void
    {
        $this->expectException(AttachmentNotFoundException::class);

        $attachRepo = $this->createMock(TenantAttachmentRepositoryInterface::class);
        $attachRepo->method('find')->willReturn(null);

        $strategy = $this->createMock(AttachmentStorageStrategyInterface::class);

        $service = new DeleteTenantAttachmentService($attachRepo, $strategy);
        $method  = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, ['attachment_id' => 9999]);
    }

    public function test_delete_tenant_attachment_service_deletes_file_and_record(): void
    {
        $attachment = $this->createTestAttachment(5, 1);

        $attachRepo = $this->createMock(TenantAttachmentRepositoryInterface::class);
        $attachRepo->method('find')->willReturn($attachment);
        $attachRepo->expects($this->once())->method('delete')->with(5)->willReturn(true);

        $strategy = $this->createMock(AttachmentStorageStrategyInterface::class);
        $strategy->expects($this->once())->method('delete')->with($attachment->getFilePath())->willReturn(true);

        $service = new DeleteTenantAttachmentService($attachRepo, $strategy);
        $method  = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $result = $method->invoke($service, ['attachment_id' => 5]);

        $this->assertTrue($result);
    }

    // ── TenantAttachmentController ────────────────────────────────────────────

    public function test_tenant_attachment_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(TenantAttachmentController::class));
    }

    public function test_tenant_attachment_controller_injects_find_service_not_repo_directly(): void
    {
        $rc = new \ReflectionClass(TenantAttachmentController::class);

        $paramTypes = [];
        foreach ($rc->getConstructor()->getParameters() as $param) {
            $paramTypes[] = $param->getType() ? $param->getType()->getName() : null;
        }

        $this->assertContains(
            FindTenantAttachmentsServiceInterface::class,
            $paramTypes,
            'TenantAttachmentController must inject FindTenantAttachmentsServiceInterface.'
        );

        $this->assertNotContains(
            TenantAttachmentRepositoryInterface::class,
            $paramTypes,
            'TenantAttachmentController must not inject TenantAttachmentRepositoryInterface directly.'
        );
    }

    public function test_tenant_attachment_controller_injects_storage_strategy_not_file_storage_directly(): void
    {
        $rc = new \ReflectionClass(TenantAttachmentController::class);

        $paramTypes = [];
        foreach ($rc->getConstructor()->getParameters() as $param) {
            $paramTypes[] = $param->getType() ? $param->getType()->getName() : null;
        }

        $this->assertContains(
            AttachmentStorageStrategyInterface::class,
            $paramTypes,
            'TenantAttachmentController must inject AttachmentStorageStrategyInterface.'
        );

        $this->assertNotContains(
            \Modules\Core\Application\Contracts\FileStorageServiceInterface::class,
            $paramTypes,
            'TenantAttachmentController must not inject FileStorageServiceInterface directly.'
        );
    }

    public function test_tenant_attachment_controller_injects_bulk_upload_service(): void
    {
        $rc = new \ReflectionClass(TenantAttachmentController::class);

        $paramTypes = [];
        foreach ($rc->getConstructor()->getParameters() as $param) {
            $paramTypes[] = $param->getType() ? $param->getType()->getName() : null;
        }

        $this->assertContains(
            BulkUploadTenantAttachmentsServiceInterface::class,
            $paramTypes,
            'TenantAttachmentController must inject BulkUploadTenantAttachmentsServiceInterface.'
        );
    }

    public function test_tenant_attachment_controller_has_store_many_method(): void
    {
        $rc = new \ReflectionClass(TenantAttachmentController::class);
        $this->assertTrue(
            $rc->hasMethod('storeMany'),
            'TenantAttachmentController must have a storeMany() method for bulk uploads.'
        );
    }

    // ── TenantController ──────────────────────────────────────────────────────

    public function test_tenant_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(TenantController::class));
    }

    public function test_tenant_controller_injects_upload_attachment_service(): void
    {
        $rc = new \ReflectionClass(TenantController::class);

        $paramTypes = [];
        foreach ($rc->getConstructor()->getParameters() as $param) {
            $paramTypes[] = $param->getType() ? $param->getType()->getName() : null;
        }

        $this->assertContains(
            UploadTenantAttachmentServiceInterface::class,
            $paramTypes,
            'TenantController must inject UploadTenantAttachmentServiceInterface for optional logo handling.'
        );
    }

    // ── Request classes ───────────────────────────────────────────────────────

    public function test_upload_tenant_attachment_request_class_exists(): void
    {
        $this->assertTrue(class_exists(UploadTenantAttachmentRequest::class));
    }

    public function test_upload_tenant_attachment_request_has_single_file_rule(): void
    {
        $request = new UploadTenantAttachmentRequest();
        $rules   = $request->rules();

        $this->assertArrayHasKey('file', $rules);
    }

    public function test_upload_tenant_attachment_request_has_bulk_files_rule(): void
    {
        $request = new UploadTenantAttachmentRequest();
        $rules   = $request->rules();

        $this->assertArrayHasKey('files', $rules);
        $this->assertArrayHasKey('files.*', $rules);
    }

    public function test_store_tenant_request_has_optional_logo_rule(): void
    {
        $request = new StoreTenantRequest();
        $rules   = $request->rules();

        $this->assertArrayHasKey('logo', $rules);
        $this->assertStringContainsString('nullable', $rules['logo']);
    }

    public function test_update_tenant_request_has_optional_logo_rule(): void
    {
        $request = new UpdateTenantRequest();
        $rules   = $request->rules();

        $this->assertArrayHasKey('logo', $rules);
        $this->assertStringContainsString('nullable', $rules['logo']);
    }

    // ── Resource ──────────────────────────────────────────────────────────────

    public function test_tenant_attachment_resource_class_exists(): void
    {
        $this->assertTrue(class_exists(TenantAttachmentResource::class));
    }

    // ── Service provider ─────────────────────────────────────────────────────

    public function test_tenant_service_provider_class_exists(): void
    {
        $this->assertTrue(class_exists(TenantServiceProvider::class));
    }

    public function test_tenant_service_provider_registers_attachment_storage_strategy(): void
    {
        $rc = new \ReflectionClass(TenantServiceProvider::class);
        $source = file_get_contents($rc->getFileName());

        $this->assertStringContainsString(
            'AttachmentStorageStrategyInterface::class',
            $source,
            'TenantServiceProvider must bind AttachmentStorageStrategyInterface.'
        );
        $this->assertStringContainsString(
            'DefaultAttachmentStorageStrategy',
            $source,
            'TenantServiceProvider must bind DefaultAttachmentStorageStrategy.'
        );
    }

    public function test_tenant_service_provider_registers_find_attachments_service(): void
    {
        $rc = new \ReflectionClass(TenantServiceProvider::class);
        $source = file_get_contents($rc->getFileName());

        $this->assertStringContainsString(
            'FindTenantAttachmentsServiceInterface::class',
            $source
        );
        $this->assertStringContainsString(
            'FindTenantAttachmentsService',
            $source
        );
    }

    public function test_tenant_service_provider_registers_bulk_upload_service(): void
    {
        $rc = new \ReflectionClass(TenantServiceProvider::class);
        $source = file_get_contents($rc->getFileName());

        $this->assertStringContainsString(
            'BulkUploadTenantAttachmentsServiceInterface::class',
            $source
        );
        $this->assertStringContainsString(
            'BulkUploadTenantAttachmentsService',
            $source
        );
    }

    // ── Routes ────────────────────────────────────────────────────────────────

    public function test_routes_file_exists(): void
    {
        $this->assertFileExists(
            __DIR__.'/../../app/Modules/Tenant/routes/api.php'
        );
    }

    public function test_routes_file_has_bulk_upload_route(): void
    {
        $routes = file_get_contents(__DIR__.'/../../app/Modules/Tenant/routes/api.php');

        $this->assertStringContainsString(
            'bulk',
            $routes,
            'Tenant routes must include a bulk upload route.'
        );
        $this->assertStringContainsString(
            'storeMany',
            $routes,
            'Tenant routes must map to storeMany() for bulk upload.'
        );
    }

    // ── TenantAttachment entity ───────────────────────────────────────────────

    public function test_tenant_attachment_entity_class_exists(): void
    {
        $this->assertTrue(class_exists(TenantAttachment::class));
    }

    public function test_tenant_attachment_entity_can_be_constructed(): void
    {
        $attachment = $this->createTestAttachment(1, 10);

        $this->assertSame(1, $attachment->getId());
        $this->assertSame(10, $attachment->getTenantId());
        $this->assertSame('document.pdf', $attachment->getName());
        $this->assertSame('tenants/10/document.pdf', $attachment->getFilePath());
        $this->assertSame('application/pdf', $attachment->getMimeType());
        $this->assertSame(4096, $attachment->getSize());
        $this->assertSame('document', $attachment->getType());
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function createTestTenant(int $id = 1): Tenant
    {
        return new Tenant(
            name: 'Test Tenant',
            databaseConfig: DatabaseConfig::fromArray([
                'driver' => 'mysql', 'host' => 'localhost',
                'port' => 3306, 'database' => 'test_db',
                'username' => 'root', 'password' => 'secret',
            ]),
            id: $id,
        );
    }

    private function createTestAttachment(int $id = 1, int $tenantId = 1): TenantAttachment
    {
        return new TenantAttachment(
            tenantId: $tenantId,
            uuid: 'test-uuid-'.$id,
            name: 'document.pdf',
            filePath: "tenants/{$tenantId}/document.pdf",
            mimeType: 'application/pdf',
            size: 4096,
            type: 'document',
            id: $id,
        );
    }
}
