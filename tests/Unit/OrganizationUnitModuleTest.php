<?php

namespace Tests\Unit;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Modules\OrganizationUnit\Application\Contracts\AttachmentStorageStrategyInterface;
use Modules\OrganizationUnit\Application\Contracts\BulkUploadOrganizationUnitAttachmentsServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\CreateOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitAttachmentServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\FindOrganizationUnitAttachmentsServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\FindOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\MoveOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\ReplaceOrganizationUnitAttachmentServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\UpdateOrganizationUnitAttachmentServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\UpdateOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\UploadOrganizationUnitAttachmentServiceInterface;
use Modules\OrganizationUnit\Application\DTOs\OrganizationUnitAttachmentData;
use Modules\OrganizationUnit\Application\DTOs\UpdateOrganizationUnitAttachmentData;
use Modules\OrganizationUnit\Application\DTOs\UpdateOrganizationUnitData;
use Modules\OrganizationUnit\Application\Services\BulkUploadOrganizationUnitAttachmentsService;
use Modules\OrganizationUnit\Application\Services\DeleteOrganizationUnitAttachmentService;
use Modules\OrganizationUnit\Application\Services\FindOrganizationUnitAttachmentsService;
use Modules\OrganizationUnit\Application\Services\FindOrganizationUnitService;
use Modules\OrganizationUnit\Application\Services\ReplaceOrganizationUnitAttachmentService;
use Modules\OrganizationUnit\Application\Services\UpdateOrganizationUnitAttachmentService;
use Modules\OrganizationUnit\Application\Services\UpdateOrganizationUnitService;
use Modules\OrganizationUnit\Application\Services\UploadOrganizationUnitAttachmentService;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitAttachment;
use Modules\OrganizationUnit\Domain\Exceptions\AttachmentNotFoundException;
use Modules\OrganizationUnit\Domain\Exceptions\OrganizationUnitNotFoundException;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitAttachmentRepositoryInterface;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Infrastructure\Http\Controllers\OrganizationUnitAttachmentController;
use Modules\OrganizationUnit\Infrastructure\Http\Controllers\OrganizationUnitController;
use Modules\OrganizationUnit\Infrastructure\Http\Requests\UpdateOrganizationUnitAttachmentRequest;
use Modules\OrganizationUnit\Infrastructure\Http\Requests\UploadOrganizationUnitAttachmentRequest;
use Modules\OrganizationUnit\Infrastructure\Http\Resources\OrganizationUnitAttachmentResource;
use Modules\OrganizationUnit\Infrastructure\Providers\OrganizationUnitServiceProvider;
use Modules\OrganizationUnit\Infrastructure\Storage\DefaultAttachmentStorageStrategy;
use Modules\Core\Domain\ValueObjects\Name;
use PHPUnit\Framework\TestCase;

class OrganizationUnitModuleTest extends TestCase
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    private function createTestUnit(int $id = 1, int $tenantId = 1): OrganizationUnit
    {
        $unit = new OrganizationUnit(tenantId: $tenantId, name: new Name('Test Unit'));
        $ref  = new \ReflectionProperty($unit, 'id');
        $ref->setAccessible(true);
        $ref->setValue($unit, $id);

        return $unit;
    }

    private function createTestAttachment(
        int $id = 1,
        int $orgUnitId = 1,
        int $tenantId = 1
    ): OrganizationUnitAttachment {
        $attachment = new OrganizationUnitAttachment(
            tenantId:           $tenantId,
            organizationUnitId: $orgUnitId,
            uuid:               'test-uuid-' . $id,
            name:               'test-file.pdf',
            filePath:           "org-units/{$orgUnitId}/test-file.pdf",
            mimeType:           'application/pdf',
            size:               1024,
            type:               null,
            metadata:           null,
            id:                 $id,
        );

        return $attachment;
    }

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
            ->with($file, 'org-units/42')
            ->willReturn('org-units/42/doc.pdf');

        $strategy = new DefaultAttachmentStorageStrategy($fileStorage);
        $path     = $strategy->store($file, 42);

        $this->assertSame('org-units/42/doc.pdf', $path);
    }

    public function test_default_attachment_storage_strategy_delete_delegates_to_file_storage(): void
    {
        $fileStorage = $this->createMock(\Modules\Core\Application\Contracts\FileStorageServiceInterface::class);
        $fileStorage->expects($this->once())
            ->method('delete')
            ->with('org-units/42/doc.pdf')
            ->willReturn(true);

        $strategy = new DefaultAttachmentStorageStrategy($fileStorage);
        $this->assertTrue($strategy->delete('org-units/42/doc.pdf'));
    }

    // ── FindOrganizationUnitAttachmentsServiceInterface ───────────────────────

    public function test_find_org_unit_attachments_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(FindOrganizationUnitAttachmentsServiceInterface::class));
    }

    public function test_find_org_unit_attachments_service_interface_declares_required_methods(): void
    {
        $rc = new \ReflectionClass(FindOrganizationUnitAttachmentsServiceInterface::class);

        $this->assertTrue($rc->hasMethod('findByOrganizationUnit'));
        $this->assertTrue($rc->hasMethod('findByUuid'));
        $this->assertTrue($rc->hasMethod('find'));
    }

    // ── FindOrganizationUnitAttachmentsService ────────────────────────────────

    public function test_find_org_unit_attachments_service_class_exists(): void
    {
        $this->assertTrue(class_exists(FindOrganizationUnitAttachmentsService::class));
    }

    public function test_find_org_unit_attachments_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(FindOrganizationUnitAttachmentsService::class, FindOrganizationUnitAttachmentsServiceInterface::class),
            'FindOrganizationUnitAttachmentsService must implement FindOrganizationUnitAttachmentsServiceInterface.'
        );
    }

    public function test_find_org_unit_attachments_service_find_by_org_unit_delegates_to_repository(): void
    {
        $repo = $this->createMock(OrganizationUnitAttachmentRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('getByOrganizationUnit')
            ->with(7, 'invoice')
            ->willReturn(new Collection);

        $service = new FindOrganizationUnitAttachmentsService($repo);
        $result  = $service->findByOrganizationUnit(7, 'invoice');

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_find_org_unit_attachments_service_find_by_uuid_delegates_to_repository(): void
    {
        $uuid       = 'test-uuid-1234';
        $attachment = $this->createTestAttachment();

        $repo = $this->createMock(OrganizationUnitAttachmentRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('findByUuid')
            ->with($uuid)
            ->willReturn($attachment);

        $service = new FindOrganizationUnitAttachmentsService($repo);
        $this->assertSame($attachment, $service->findByUuid($uuid));
    }

    public function test_find_org_unit_attachments_service_find_returns_null_when_missing(): void
    {
        $repo = $this->createMock(OrganizationUnitAttachmentRepositoryInterface::class);
        $repo->method('find')->willReturn(null);

        $service = new FindOrganizationUnitAttachmentsService($repo);
        $this->assertNull($service->find(9999));
    }

    // ── BulkUploadOrganizationUnitAttachmentsServiceInterface ─────────────────

    public function test_bulk_upload_org_unit_attachments_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(BulkUploadOrganizationUnitAttachmentsServiceInterface::class));
    }

    // ── BulkUploadOrganizationUnitAttachmentsService ──────────────────────────

    public function test_bulk_upload_org_unit_attachments_service_class_exists(): void
    {
        $this->assertTrue(class_exists(BulkUploadOrganizationUnitAttachmentsService::class));
    }

    public function test_bulk_upload_org_unit_attachments_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(
                BulkUploadOrganizationUnitAttachmentsService::class,
                BulkUploadOrganizationUnitAttachmentsServiceInterface::class
            ),
            'BulkUploadOrganizationUnitAttachmentsService must implement BulkUploadOrganizationUnitAttachmentsServiceInterface.'
        );
    }

    public function test_bulk_upload_service_constructor_accepts_storage_strategy(): void
    {
        $orgUnitRepo = $this->createMock(OrganizationUnitRepositoryInterface::class);
        $attachRepo  = $this->createMock(OrganizationUnitAttachmentRepositoryInterface::class);
        $strategy    = $this->createMock(AttachmentStorageStrategyInterface::class);

        $service = new BulkUploadOrganizationUnitAttachmentsService($orgUnitRepo, $attachRepo, $strategy);

        $this->assertInstanceOf(BulkUploadOrganizationUnitAttachmentsServiceInterface::class, $service);
    }

    public function test_bulk_upload_service_has_correct_dependencies(): void
    {
        $rc = new \ReflectionClass(BulkUploadOrganizationUnitAttachmentsService::class);

        $paramTypes = [];
        foreach ($rc->getConstructor()->getParameters() as $param) {
            $paramTypes[] = $param->getType() ? $param->getType()->getName() : null;
        }

        $this->assertContains(OrganizationUnitRepositoryInterface::class, $paramTypes);
        $this->assertContains(OrganizationUnitAttachmentRepositoryInterface::class, $paramTypes);
        $this->assertContains(AttachmentStorageStrategyInterface::class, $paramTypes);
    }

    // ── UploadOrganizationUnitAttachmentService ───────────────────────────────

    public function test_upload_org_unit_attachment_service_uses_storage_strategy(): void
    {
        $rc = new \ReflectionClass(UploadOrganizationUnitAttachmentService::class);

        $strategyParam = null;
        foreach ($rc->getConstructor()->getParameters() as $param) {
            if ($param->getType() && $param->getType()->getName() === AttachmentStorageStrategyInterface::class) {
                $strategyParam = $param;
                break;
            }
        }

        $this->assertNotNull(
            $strategyParam,
            'UploadOrganizationUnitAttachmentService constructor must accept AttachmentStorageStrategyInterface.'
        );
    }

    public function test_upload_org_unit_attachment_service_no_longer_uses_file_storage_service_directly(): void
    {
        $rc = new \ReflectionClass(UploadOrganizationUnitAttachmentService::class);

        foreach ($rc->getConstructor()->getParameters() as $param) {
            $type = $param->getType() ? $param->getType()->getName() : null;
            $this->assertNotSame(
                \Modules\Core\Application\Contracts\FileStorageServiceInterface::class,
                $type,
                'UploadOrganizationUnitAttachmentService must not directly depend on FileStorageServiceInterface.'
            );
        }
    }

    public function test_upload_org_unit_attachment_service_throws_when_unit_not_found(): void
    {
        $this->expectException(OrganizationUnitNotFoundException::class);

        $orgUnitRepo = $this->createMock(OrganizationUnitRepositoryInterface::class);
        $orgUnitRepo->method('find')->willReturn(null);

        $attachRepo = $this->createMock(OrganizationUnitAttachmentRepositoryInterface::class);
        $strategy   = $this->createMock(AttachmentStorageStrategyInterface::class);

        $service = new UploadOrganizationUnitAttachmentService($orgUnitRepo, $attachRepo, $strategy);

        $file = $this->createMock(UploadedFile::class);
        $file->method('getClientOriginalName')->willReturn('test.pdf');
        $file->method('getMimeType')->willReturn('application/pdf');
        $file->method('getSize')->willReturn(1024);

        // Use Reflection to call protected handle() directly (avoids DB::transaction)
        $method = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, ['organization_unit_id' => 99, 'file' => $file]);
    }

    public function test_upload_org_unit_attachment_service_stores_file_via_strategy(): void
    {
        $unit = $this->createTestUnit(1, 1);

        $orgUnitRepo = $this->createMock(OrganizationUnitRepositoryInterface::class);
        $orgUnitRepo->method('find')->willReturn($unit);

        $savedAttachment = $this->createTestAttachment(1, 1);

        $attachRepo = $this->createMock(OrganizationUnitAttachmentRepositoryInterface::class);
        $attachRepo->expects($this->once())
            ->method('save')
            ->willReturn($savedAttachment);

        $strategy = $this->createMock(AttachmentStorageStrategyInterface::class);
        $strategy->expects($this->once())
            ->method('store')
            ->willReturn('org-units/1/file.pdf');

        $file = $this->createMock(UploadedFile::class);
        $file->method('getClientOriginalName')->willReturn('file.pdf');
        $file->method('getMimeType')->willReturn('application/pdf');
        $file->method('getSize')->willReturn(2048);

        $service = new UploadOrganizationUnitAttachmentService($orgUnitRepo, $attachRepo, $strategy);

        $method = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $result = $method->invoke($service, [
            'organization_unit_id' => 1,
            'file'                 => $file,
            'type'                 => null,
            'metadata'             => null,
        ]);

        $this->assertInstanceOf(OrganizationUnitAttachment::class, $result);
        $this->assertSame($savedAttachment, $result);
    }

    // ── DeleteOrganizationUnitAttachmentService ───────────────────────────────

    public function test_delete_org_unit_attachment_service_uses_storage_strategy(): void
    {
        $rc = new \ReflectionClass(DeleteOrganizationUnitAttachmentService::class);

        $strategyParam = null;
        foreach ($rc->getConstructor()->getParameters() as $param) {
            if ($param->getType() && $param->getType()->getName() === AttachmentStorageStrategyInterface::class) {
                $strategyParam = $param;
                break;
            }
        }

        $this->assertNotNull(
            $strategyParam,
            'DeleteOrganizationUnitAttachmentService constructor must accept AttachmentStorageStrategyInterface.'
        );
    }

    public function test_delete_org_unit_attachment_service_throws_when_not_found(): void
    {
        $this->expectException(AttachmentNotFoundException::class);

        $attachRepo = $this->createMock(OrganizationUnitAttachmentRepositoryInterface::class);
        $attachRepo->method('find')->willReturn(null);

        $strategy = $this->createMock(AttachmentStorageStrategyInterface::class);

        $service = new DeleteOrganizationUnitAttachmentService($attachRepo, $strategy);

        $method = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, ['attachment_id' => 99]);
    }

    public function test_delete_org_unit_attachment_service_deletes_file_and_record(): void
    {
        $attachment = $this->createTestAttachment(5, 1);

        $attachRepo = $this->createMock(OrganizationUnitAttachmentRepositoryInterface::class);
        $attachRepo->method('find')->willReturn($attachment);
        $attachRepo->expects($this->once())->method('delete')->with(5)->willReturn(true);

        $strategy = $this->createMock(AttachmentStorageStrategyInterface::class);
        $strategy->expects($this->once())
            ->method('delete')
            ->with('org-units/1/test-file.pdf');

        $service = new DeleteOrganizationUnitAttachmentService($attachRepo, $strategy);

        $method = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $result = $method->invoke($service, ['attachment_id' => 5]);

        $this->assertTrue($result);
    }

    // ── OrganizationUnitAttachmentController ──────────────────────────────────

    public function test_org_unit_attachment_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(OrganizationUnitAttachmentController::class));
    }

    public function test_org_unit_attachment_controller_injects_find_service_not_repo_directly(): void
    {
        $rc = new \ReflectionClass(OrganizationUnitAttachmentController::class);

        $paramTypes = [];
        foreach ($rc->getConstructor()->getParameters() as $param) {
            $paramTypes[] = $param->getType() ? $param->getType()->getName() : null;
        }

        $this->assertContains(FindOrganizationUnitAttachmentsServiceInterface::class, $paramTypes,
            'Controller must inject FindOrganizationUnitAttachmentsServiceInterface.');

        $this->assertNotContains(\Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitAttachmentRepositoryInterface::class, $paramTypes,
            'Controller must NOT directly inject OrganizationUnitAttachmentRepositoryInterface.');
    }

    public function test_org_unit_attachment_controller_injects_storage_strategy_not_file_storage_directly(): void
    {
        $rc = new \ReflectionClass(OrganizationUnitAttachmentController::class);

        $paramTypes = [];
        foreach ($rc->getConstructor()->getParameters() as $param) {
            $paramTypes[] = $param->getType() ? $param->getType()->getName() : null;
        }

        $this->assertContains(AttachmentStorageStrategyInterface::class, $paramTypes,
            'Controller must inject AttachmentStorageStrategyInterface.');

        $this->assertNotContains(\Modules\Core\Application\Contracts\FileStorageServiceInterface::class, $paramTypes,
            'Controller must NOT directly inject FileStorageServiceInterface.');
    }

    public function test_org_unit_attachment_controller_injects_bulk_upload_service(): void
    {
        $rc = new \ReflectionClass(OrganizationUnitAttachmentController::class);

        $paramTypes = [];
        foreach ($rc->getConstructor()->getParameters() as $param) {
            $paramTypes[] = $param->getType() ? $param->getType()->getName() : null;
        }

        $this->assertContains(BulkUploadOrganizationUnitAttachmentsServiceInterface::class, $paramTypes,
            'Controller must inject BulkUploadOrganizationUnitAttachmentsServiceInterface.');
    }

    public function test_org_unit_attachment_controller_has_store_many_method(): void
    {
        $rc = new \ReflectionClass(OrganizationUnitAttachmentController::class);
        $this->assertTrue($rc->hasMethod('storeMany'),
            'OrganizationUnitAttachmentController must have a storeMany() method for bulk uploads.');
    }

    // ── OrganizationUnitController ────────────────────────────────────────────

    public function test_org_unit_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(OrganizationUnitController::class));
    }

    public function test_org_unit_controller_injects_find_service_not_repository_directly(): void
    {
        $rc = new \ReflectionClass(OrganizationUnitController::class);

        $paramTypes = [];
        foreach ($rc->getConstructor()->getParameters() as $param) {
            $paramTypes[] = $param->getType() ? $param->getType()->getName() : null;
        }

        $this->assertContains(FindOrganizationUnitServiceInterface::class, $paramTypes,
            'OrganizationUnitController must inject FindOrganizationUnitServiceInterface.');

        $this->assertNotContains(\Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface::class, $paramTypes,
            'OrganizationUnitController must NOT directly inject OrganizationUnitRepositoryInterface.');
    }

    public function test_org_unit_controller_injects_create_service(): void
    {
        $rc = new \ReflectionClass(OrganizationUnitController::class);

        $paramTypes = [];
        foreach ($rc->getConstructor()->getParameters() as $param) {
            $paramTypes[] = $param->getType() ? $param->getType()->getName() : null;
        }

        $this->assertContains(CreateOrganizationUnitServiceInterface::class, $paramTypes,
            'OrganizationUnitController must inject CreateOrganizationUnitServiceInterface.');
    }

    public function test_org_unit_controller_injects_update_service(): void
    {
        $rc = new \ReflectionClass(OrganizationUnitController::class);

        $paramTypes = [];
        foreach ($rc->getConstructor()->getParameters() as $param) {
            $paramTypes[] = $param->getType() ? $param->getType()->getName() : null;
        }

        $this->assertContains(UpdateOrganizationUnitServiceInterface::class, $paramTypes,
            'OrganizationUnitController must inject UpdateOrganizationUnitServiceInterface.');
    }

    public function test_org_unit_controller_injects_delete_service(): void
    {
        $rc = new \ReflectionClass(OrganizationUnitController::class);

        $paramTypes = [];
        foreach ($rc->getConstructor()->getParameters() as $param) {
            $paramTypes[] = $param->getType() ? $param->getType()->getName() : null;
        }

        $this->assertContains(DeleteOrganizationUnitServiceInterface::class, $paramTypes,
            'OrganizationUnitController must inject DeleteOrganizationUnitServiceInterface.');
    }

    public function test_org_unit_controller_injects_move_service(): void
    {
        $rc = new \ReflectionClass(OrganizationUnitController::class);

        $paramTypes = [];
        foreach ($rc->getConstructor()->getParameters() as $param) {
            $paramTypes[] = $param->getType() ? $param->getType()->getName() : null;
        }

        $this->assertContains(MoveOrganizationUnitServiceInterface::class, $paramTypes,
            'OrganizationUnitController must inject MoveOrganizationUnitServiceInterface.');
    }

    // ── FindOrganizationUnitServiceInterface ──────────────────────────────────

    public function test_find_org_unit_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(FindOrganizationUnitServiceInterface::class));
    }

    public function test_find_org_unit_service_interface_declares_required_methods(): void
    {
        $rc = new \ReflectionClass(FindOrganizationUnitServiceInterface::class);

        $this->assertTrue($rc->hasMethod('find'));
        $this->assertTrue($rc->hasMethod('list'));
        $this->assertTrue($rc->hasMethod('getTree'));
    }

    // ── FindOrganizationUnitService ───────────────────────────────────────────

    public function test_find_org_unit_service_class_exists(): void
    {
        $this->assertTrue(class_exists(FindOrganizationUnitService::class));
    }

    public function test_find_org_unit_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(FindOrganizationUnitService::class, FindOrganizationUnitServiceInterface::class),
            'FindOrganizationUnitService must implement FindOrganizationUnitServiceInterface.'
        );
    }

    public function test_find_org_unit_service_find_delegates_to_repository(): void
    {
        $unit = $this->createTestUnit(3, 1);

        $repo = $this->createMock(OrganizationUnitRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('find')
            ->with(3)
            ->willReturn($unit);

        $service = new FindOrganizationUnitService($repo);
        $result  = $service->find(3);

        $this->assertSame($unit, $result);
    }

    public function test_find_org_unit_service_find_returns_null_when_missing(): void
    {
        $repo = $this->createMock(OrganizationUnitRepositoryInterface::class);
        $repo->method('find')->willReturn(null);

        $service = new FindOrganizationUnitService($repo);
        $this->assertNull($service->find(9999));
    }

    public function test_find_org_unit_service_get_tree_delegates_to_repository(): void
    {
        $tree = [['id' => 1, 'name' => 'Root', 'children' => []]];

        $repo = $this->createMock(OrganizationUnitRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('getTree')
            ->with(1, null)
            ->willReturn($tree);

        $service = new FindOrganizationUnitService($repo);
        $result  = $service->getTree(1, null);

        $this->assertSame($tree, $result);
    }

    public function test_find_org_unit_service_get_tree_with_root_id(): void
    {
        $tree = [['id' => 5, 'name' => 'Sub', 'children' => []]];

        $repo = $this->createMock(OrganizationUnitRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('getTree')
            ->with(2, 5)
            ->willReturn($tree);

        $service = new FindOrganizationUnitService($repo);
        $result  = $service->getTree(2, 5);

        $this->assertSame($tree, $result);
    }

    public function test_find_org_unit_service_handle_throws_bad_method_call_exception(): void
    {
        $this->expectException(\BadMethodCallException::class);

        $repo    = $this->createMock(OrganizationUnitRepositoryInterface::class);
        $service = new FindOrganizationUnitService($repo);

        $method = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, []);
    }

    public function test_find_org_unit_service_does_not_depend_on_attachment_repository(): void
    {
        $rc = new \ReflectionClass(FindOrganizationUnitService::class);

        foreach ($rc->getConstructor()->getParameters() as $param) {
            $type = $param->getType() ? $param->getType()->getName() : null;
            $this->assertNotSame(
                \Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitAttachmentRepositoryInterface::class,
                $type,
                'FindOrganizationUnitService must not depend on OrganizationUnitAttachmentRepositoryInterface.'
            );
        }
    }

    // ── UploadOrganizationUnitAttachmentRequest ───────────────────────────────

    public function test_upload_org_unit_attachment_request_class_exists(): void
    {
        $this->assertTrue(class_exists(UploadOrganizationUnitAttachmentRequest::class));
    }

    public function test_upload_org_unit_attachment_request_has_single_file_rule(): void
    {
        $request = new UploadOrganizationUnitAttachmentRequest;
        $rules   = $request->rules();

        $this->assertArrayHasKey('file', $rules,
            'Request must define a validation rule for single file upload.');
    }

    public function test_upload_org_unit_attachment_request_has_bulk_files_rule(): void
    {
        $request = new UploadOrganizationUnitAttachmentRequest;
        $rules   = $request->rules();

        $this->assertArrayHasKey('files', $rules,
            'Request must define a validation rule for bulk file uploads.');
        $this->assertArrayHasKey('files.*', $rules,
            'Request must define a validation rule for each file in the bulk upload.');
    }

    public function test_upload_org_unit_attachment_request_has_with_validator_method(): void
    {
        $rc = new \ReflectionClass(UploadOrganizationUnitAttachmentRequest::class);
        $this->assertTrue($rc->hasMethod('withValidator'),
            'Request must define withValidator() to enforce at-least-one-file constraint.');
    }

    public function test_upload_org_unit_attachment_request_file_rule_is_nullable(): void
    {
        $request = new UploadOrganizationUnitAttachmentRequest;
        $rules   = $request->rules();

        $this->assertStringContainsString('nullable', $rules['file'],
            'The single file field should be nullable to support bulk-only uploads.');
    }

    // ── OrganizationUnitAttachmentResource ────────────────────────────────────

    public function test_org_unit_attachment_resource_class_exists(): void
    {
        $this->assertTrue(class_exists(OrganizationUnitAttachmentResource::class));
    }

    // ── OrganizationUnitServiceProvider ──────────────────────────────────────

    public function test_org_unit_service_provider_class_exists(): void
    {
        $this->assertTrue(class_exists(OrganizationUnitServiceProvider::class));
    }

    public function test_org_unit_service_provider_registers_attachment_storage_strategy(): void
    {
        $rc       = new \ReflectionClass(OrganizationUnitServiceProvider::class);
        $method   = $rc->getMethod('register');
        $filename = $rc->getFileName();
        $start    = $method->getStartLine();
        $end      = $method->getEndLine();
        $lines    = array_slice(file($filename), $start - 1, $end - $start + 1);
        $body     = implode('', $lines);

        $this->assertStringContainsString('AttachmentStorageStrategyInterface', $body,
            'ServiceProvider must bind AttachmentStorageStrategyInterface.');
    }

    public function test_org_unit_service_provider_registers_find_attachments_service(): void
    {
        $rc       = new \ReflectionClass(OrganizationUnitServiceProvider::class);
        $method   = $rc->getMethod('register');
        $filename = $rc->getFileName();
        $start    = $method->getStartLine();
        $end      = $method->getEndLine();
        $lines    = array_slice(file($filename), $start - 1, $end - $start + 1);
        $body     = implode('', $lines);

        $this->assertStringContainsString('FindOrganizationUnitAttachmentsServiceInterface', $body,
            'ServiceProvider must bind FindOrganizationUnitAttachmentsServiceInterface.');
    }

    public function test_org_unit_service_provider_registers_bulk_upload_service(): void
    {
        $rc       = new \ReflectionClass(OrganizationUnitServiceProvider::class);
        $method   = $rc->getMethod('register');
        $filename = $rc->getFileName();
        $start    = $method->getStartLine();
        $end      = $method->getEndLine();
        $lines    = array_slice(file($filename), $start - 1, $end - $start + 1);
        $body     = implode('', $lines);

        $this->assertStringContainsString('BulkUploadOrganizationUnitAttachmentsServiceInterface', $body,
            'ServiceProvider must bind BulkUploadOrganizationUnitAttachmentsServiceInterface.');
    }

    public function test_org_unit_service_provider_registers_find_org_unit_service(): void
    {
        $rc       = new \ReflectionClass(OrganizationUnitServiceProvider::class);
        $method   = $rc->getMethod('register');
        $filename = $rc->getFileName();
        $start    = $method->getStartLine();
        $end      = $method->getEndLine();
        $lines    = array_slice(file($filename), $start - 1, $end - $start + 1);
        $body     = implode('', $lines);

        $this->assertStringContainsString('FindOrganizationUnitServiceInterface', $body,
            'ServiceProvider must bind FindOrganizationUnitServiceInterface.');
    }

    // ── Routes ────────────────────────────────────────────────────────────────

    public function test_routes_file_exists(): void
    {
        $routesFile = __DIR__.'/../../app/Modules/OrganizationUnit/routes/api.php';
        $this->assertFileExists($routesFile);
    }

    public function test_routes_file_has_bulk_upload_route(): void
    {
        $routesFile = __DIR__.'/../../app/Modules/OrganizationUnit/routes/api.php';
        $content    = file_get_contents($routesFile);

        $this->assertStringContainsString('storeMany', $content,
            'Routes must include a bulk upload endpoint (storeMany).');
        $this->assertStringContainsString('bulk', $content,
            'Routes must include a /bulk path segment for the bulk upload endpoint.');
    }

    // ── OrganizationUnitAttachment Entity ─────────────────────────────────────

    public function test_org_unit_attachment_entity_class_exists(): void
    {
        $this->assertTrue(class_exists(OrganizationUnitAttachment::class));
    }

    public function test_org_unit_attachment_entity_can_be_constructed(): void
    {
        $attachment = $this->createTestAttachment(1, 2, 3);

        $this->assertSame(3, $attachment->getTenantId());
        $this->assertSame(2, $attachment->getOrganizationUnitId());
        $this->assertSame('test-uuid-1', $attachment->getUuid());
        $this->assertSame('test-file.pdf', $attachment->getName());
        $this->assertSame('application/pdf', $attachment->getMimeType());
        $this->assertSame(1024, $attachment->getSize());
    }

    // ── UpdateOrganizationUnitRequest ─────────────────────────────────────────

    public function test_update_org_unit_request_does_not_require_tenant_id(): void
    {
        $request = new \Modules\OrganizationUnit\Infrastructure\Http\Requests\UpdateOrganizationUnitRequest;
        $rules   = $request->rules();

        $this->assertArrayNotHasKey('tenant_id', $rules,
            'UpdateOrganizationUnitRequest must not require tenant_id — tenants never change on update.');
    }

    public function test_update_org_unit_request_name_uses_sometimes_rule(): void
    {
        $request = new \Modules\OrganizationUnit\Infrastructure\Http\Requests\UpdateOrganizationUnitRequest;
        $rules   = $request->rules();

        $this->assertArrayHasKey('name', $rules,
            'UpdateOrganizationUnitRequest must declare a rule for name.');
        $this->assertStringContainsString('sometimes', $rules['name'],
            'The name rule must use "sometimes" to support partial updates.');
    }

    // ── UpdateOrganizationUnitData DTO ────────────────────────────────────────

    public function test_update_org_unit_data_dto_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdateOrganizationUnitData::class));
    }

    public function test_update_org_unit_data_dto_extends_base_dto(): void
    {
        $this->assertTrue(
            is_subclass_of(UpdateOrganizationUnitData::class, \Modules\Core\Application\DTOs\BaseDto::class),
            'UpdateOrganizationUnitData must extend BaseDto.'
        );
    }

    public function test_update_org_unit_data_dto_to_array_only_returns_provided_keys(): void
    {
        $dto = UpdateOrganizationUnitData::fromArray(['id' => 5, 'name' => 'New Name']);

        $arr = $dto->toArray();

        $this->assertArrayHasKey('id',   $arr, 'id must be present because it was provided.');
        $this->assertArrayHasKey('name', $arr, 'name must be present because it was provided.');
        $this->assertArrayNotHasKey('code',        $arr, 'code must be absent because it was not provided.');
        $this->assertArrayNotHasKey('description', $arr, 'description must be absent because it was not provided.');
        $this->assertArrayNotHasKey('metadata',    $arr, 'metadata must be absent because it was not provided.');
        $this->assertArrayNotHasKey('parent_id',   $arr, 'parent_id must be absent because it was not provided.');
    }

    public function test_update_org_unit_data_dto_is_provided_returns_true_for_present_fields(): void
    {
        $dto = UpdateOrganizationUnitData::fromArray(['name' => 'Dept A', 'code' => null]);

        $this->assertTrue($dto->isProvided('name'),  'name was in the source array — isProvided must return true.');
        $this->assertTrue($dto->isProvided('code'),  'code was in the source array (as null) — isProvided must return true.');
        $this->assertFalse($dto->isProvided('description'), 'description was absent — isProvided must return false.');
        $this->assertFalse($dto->isProvided('metadata'),    'metadata was absent — isProvided must return false.');
    }

    public function test_update_org_unit_data_dto_has_nullable_name(): void
    {
        $rc   = new \ReflectionClass(UpdateOrganizationUnitData::class);
        $prop = $rc->getProperty('name');

        $this->assertTrue(
            $prop->getType()->allowsNull(),
            'UpdateOrganizationUnitData::$name must be nullable to support partial updates.'
        );
    }

    public function test_update_org_unit_data_dto_does_not_have_tenant_id_property(): void
    {
        $rc = new \ReflectionClass(UpdateOrganizationUnitData::class);

        $propNames = array_map(fn ($p) => $p->getName(), $rc->getProperties(\ReflectionProperty::IS_PUBLIC));

        $this->assertNotContains('tenant_id', $propNames,
            'UpdateOrganizationUnitData must not have a tenant_id property — tenants never change on update.');
    }

    // ── UpdateOrganizationUnitService — DTO usage ─────────────────────────────

    public function test_update_org_unit_service_uses_update_org_unit_data_dto(): void
    {
        $rc       = new \ReflectionClass(UpdateOrganizationUnitService::class);
        $filename = $rc->getFileName();
        $source   = file_get_contents($filename);

        $this->assertStringContainsString('UpdateOrganizationUnitData::fromArray',
            $source,
            'UpdateOrganizationUnitService must build UpdateOrganizationUnitData from the input array.');
    }

    public function test_update_org_unit_service_throws_when_unit_not_found(): void
    {
        $this->expectException(\Modules\OrganizationUnit\Domain\Exceptions\OrganizationUnitNotFoundException::class);

        $repo = $this->createMock(\Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface::class);
        $repo->method('find')->willReturn(null);

        $service = new UpdateOrganizationUnitService($repo);

        $method = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, ['id' => 99, 'name' => 'New Name']);
    }

    public function test_update_org_unit_service_updates_full_fields(): void
    {
        $unit = $this->createTestUnit(1, 1);

        $repo = $this->createMock(\Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface::class);
        $repo->expects($this->once())->method('find')->with(1)->willReturn($unit);
        $repo->expects($this->once())->method('save')->willReturn($unit);

        $service = new UpdateOrganizationUnitService($repo);

        $method = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $result = $method->invoke($service, [
            'id'          => 1,
            'name'        => 'Updated Name',
            'code'        => 'UPD',
            'description' => 'New description',
            'metadata'    => ['key' => 'value'],
        ]);

        $this->assertInstanceOf(\Modules\OrganizationUnit\Domain\Entities\OrganizationUnit::class, $result);
    }

    public function test_update_org_unit_service_partial_update_preserves_existing_name(): void
    {
        $unit         = $this->createTestUnit(2, 1);
        $originalName = $unit->getName();

        $repo = $this->createMock(\Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface::class);
        $repo->expects($this->once())->method('find')->with(2)->willReturn($unit);
        $repo->expects($this->once())->method('save')->willReturn($unit);

        $service = new UpdateOrganizationUnitService($repo);

        $method = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        // Only update description — name is intentionally omitted.
        $method->invoke($service, [
            'id'          => 2,
            'description' => 'Only description changed',
        ]);

        $this->assertSame($originalName, $unit->getName(),
            'The entity name must be preserved when name is not included in the update payload.');
    }

    public function test_update_org_unit_service_move_only_when_parent_id_changes(): void
    {
        $unit = $this->createTestUnit(3, 1);

        $repo = $this->createMock(\Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface::class);
        $repo->expects($this->once())->method('find')->with(3)->willReturn($unit);
        $repo->expects($this->never())->method('moveNode');
        $repo->expects($this->once())->method('save')->willReturn($unit);

        $service = new UpdateOrganizationUnitService($repo);

        $method = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        // parent_id not supplied — moveNode must not be called.
        $method->invoke($service, ['id' => 3, 'name' => 'Stable Name']);
    }

    // ── OrganizationUnitController — DTO usage ────────────────────────────────

    public function test_org_unit_controller_imports_org_unit_data_dto(): void
    {
        $rc       = new \ReflectionClass(OrganizationUnitController::class);
        $filename = $rc->getFileName();
        $source   = file_get_contents($filename);

        $this->assertStringContainsString(
            'use Modules\OrganizationUnit\Application\DTOs\OrganizationUnitData',
            $source,
            'OrganizationUnitController must import OrganizationUnitData for the store() action.'
        );
    }

    public function test_org_unit_controller_imports_update_org_unit_data_dto(): void
    {
        $rc       = new \ReflectionClass(OrganizationUnitController::class);
        $filename = $rc->getFileName();
        $source   = file_get_contents($filename);

        $this->assertStringContainsString(
            'use Modules\OrganizationUnit\Application\DTOs\UpdateOrganizationUnitData',
            $source,
            'OrganizationUnitController must import UpdateOrganizationUnitData for the update() action.'
        );
    }

    public function test_update_org_unit_service_preserves_metadata_when_omitted(): void
    {
        // Build a unit that already has metadata.
        $unit             = $this->createTestUnit(10, 1);
        $existingMetadata = new \Modules\Core\Domain\ValueObjects\Metadata(['org_type' => 'dept']);
        // Inject the metadata through updateDetails so the entity has a real Metadata VO.
        $unit->updateDetails($unit->getName(), $unit->getCode(), $unit->getDescription(), $existingMetadata);

        $repo = $this->createMock(\Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface::class);
        $repo->expects($this->once())->method('find')->with(10)->willReturn($unit);
        $repo->expects($this->once())->method('save')->willReturn($unit);

        $service = new UpdateOrganizationUnitService($repo);
        $method  = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);

        // Omit metadata entirely — the existing metadata must be unchanged.
        $method->invoke($service, ['id' => 10, 'name' => 'New Name']);

        $this->assertSame(
            $existingMetadata,
            $unit->getMetadata(),
            'Entity metadata must not be modified when metadata is omitted from the update payload.'
        );
    }

    // ── OrganizationUnit entity — updateDetails ───────────────────────────────

    public function test_org_unit_entity_update_details_updates_all_fields(): void
    {
        $unit = $this->createTestUnit(1, 1);
        $name = new \Modules\Core\Domain\ValueObjects\Name('Updated');
        $code = new \Modules\Core\Domain\ValueObjects\Code('UPD');
        $meta = new \Modules\Core\Domain\ValueObjects\Metadata(['k' => 'v']);

        $unit->updateDetails($name, $code, 'New desc', $meta);

        $this->assertSame('Updated', $unit->getName()->value());
        $this->assertSame('UPD', $unit->getCode()->value());
        $this->assertSame('New desc', $unit->getDescription());
        $this->assertSame(['k' => 'v'], $unit->getMetadata()->toArray());
    }

    public function test_org_unit_entity_update_details_clears_metadata_when_explicitly_null(): void
    {
        $unit = $this->createTestUnit(1, 1);
        $meta = new \Modules\Core\Domain\ValueObjects\Metadata(['existing' => true]);
        $unit->updateDetails($unit->getName(), $unit->getCode(), null, $meta);

        // Now explicitly pass null — metadata should be reset to empty.
        $unit->updateDetails($unit->getName(), $unit->getCode(), null, null);

        $this->assertSame(
            [],
            $unit->getMetadata()->toArray(),
            'updateDetails must reset metadata to an empty Metadata when explicitly passed null.'
        );
    }

    public function test_org_unit_entity_update_details_clears_code_when_null(): void
    {
        $unit = $this->createTestUnit(1, 1);
        $unit->updateDetails(
            $unit->getName(),
            new \Modules\Core\Domain\ValueObjects\Code('ORIG'),
            null,
            null
        );
        $this->assertNotNull($unit->getCode());

        $unit->updateDetails($unit->getName(), null, null, null);
        $this->assertNull($unit->getCode());
    }

    // ── OrganizationUnitResource — null-safe code ─────────────────────────────

    public function test_org_unit_resource_handles_null_code(): void
    {
        $unit     = $this->createTestUnit(1, 1);
        $resource = new \Modules\OrganizationUnit\Infrastructure\Http\Resources\OrganizationUnitResource($unit);
        $arr      = $resource->toArray(null);

        $this->assertArrayHasKey('code', $arr);
        $this->assertNull($arr['code'],
            'OrganizationUnitResource must return null for code when the entity has no code set.');
    }

    // ── FindOrganizationUnitServiceInterface — getDescendants / getAncestors ──

    public function test_find_org_unit_service_interface_declares_get_descendants(): void
    {
        $rc = new \ReflectionClass(\Modules\OrganizationUnit\Application\Contracts\FindOrganizationUnitServiceInterface::class);

        $this->assertTrue(
            $rc->hasMethod('getDescendants'),
            'FindOrganizationUnitServiceInterface must declare getDescendants(int $id): array.'
        );
    }

    public function test_find_org_unit_service_interface_declares_get_ancestors(): void
    {
        $rc = new \ReflectionClass(\Modules\OrganizationUnit\Application\Contracts\FindOrganizationUnitServiceInterface::class);

        $this->assertTrue(
            $rc->hasMethod('getAncestors'),
            'FindOrganizationUnitServiceInterface must declare getAncestors(int $id): array.'
        );
    }

    public function test_find_org_unit_service_get_descendants_delegates_to_repository(): void
    {
        $repo = $this->createMock(\Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface::class);
        $unit = $this->createTestUnit(2, 1);
        $repo->expects($this->once())
            ->method('getDescendants')
            ->with(1)
            ->willReturn([$unit]);

        $service = new FindOrganizationUnitService($repo);

        $result = $service->getDescendants(1);

        $this->assertCount(1, $result);
        $this->assertSame($unit, $result[0]);
    }

    public function test_find_org_unit_service_get_ancestors_delegates_to_repository(): void
    {
        $repo = $this->createMock(\Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface::class);
        $unit = $this->createTestUnit(5, 1);
        $repo->expects($this->once())
            ->method('getAncestors')
            ->with(10)
            ->willReturn([$unit]);

        $service = new FindOrganizationUnitService($repo);

        $result = $service->getAncestors(10);

        $this->assertCount(1, $result);
        $this->assertSame($unit, $result[0]);
    }

    // ── OrganizationUnitController — descendants / ancestors actions ──────────

    public function test_org_unit_controller_has_descendants_method(): void
    {
        $rc = new \ReflectionClass(OrganizationUnitController::class);

        $this->assertTrue(
            $rc->hasMethod('descendants'),
            'OrganizationUnitController must declare a descendants() action.'
        );
    }

    public function test_org_unit_controller_has_ancestors_method(): void
    {
        $rc = new \ReflectionClass(OrganizationUnitController::class);

        $this->assertTrue(
            $rc->hasMethod('ancestors'),
            'OrganizationUnitController must declare an ancestors() action.'
        );
    }

    // ── Routes — static routes precede wildcard ───────────────────────────────

    public function test_routes_tree_declared_before_api_resource(): void
    {
        $routesFile = __DIR__.'/../../app/Modules/OrganizationUnit/routes/api.php';
        $content    = file_get_contents($routesFile);

        $treePos        = strpos($content, "'tree'");
        $apiResourcePos = strpos($content, 'apiResource');

        $this->assertNotFalse($treePos,        'Routes file must define the tree route.');
        $this->assertNotFalse($apiResourcePos, 'Routes file must define an apiResource route.');
        $this->assertLessThan(
            $apiResourcePos,
            $treePos,
            'The tree route must be declared BEFORE apiResource to prevent the {unit} wildcard swallowing it.'
        );
    }

    public function test_routes_file_has_descendants_route(): void
    {
        $routesFile = __DIR__.'/../../app/Modules/OrganizationUnit/routes/api.php';
        $content    = file_get_contents($routesFile);

        $this->assertStringContainsString('descendants', $content,
            'Routes must include a descendants endpoint.');
    }

    public function test_routes_file_has_ancestors_route(): void
    {
        $routesFile = __DIR__.'/../../app/Modules/OrganizationUnit/routes/api.php';
        $content    = file_get_contents($routesFile);

        $this->assertStringContainsString('ancestors', $content,
            'Routes must include an ancestors endpoint.');
    }

    // ── AttachmentStorageStrategyInterface — url() method ────────────────────

    public function test_attachment_storage_strategy_interface_declares_url_method(): void
    {
        $rc = new \ReflectionClass(AttachmentStorageStrategyInterface::class);

        $this->assertTrue(
            $rc->hasMethod('url'),
            'AttachmentStorageStrategyInterface must declare url(string $path): string.'
        );
    }

    public function test_default_attachment_storage_strategy_url_delegates_to_file_storage(): void
    {
        $fileStorage = $this->createMock(\Modules\Core\Application\Contracts\FileStorageServiceInterface::class);
        $fileStorage->expects($this->once())
            ->method('url')
            ->with('org-units/1/file.pdf')
            ->willReturn('https://example.com/file.pdf');

        $strategy = new DefaultAttachmentStorageStrategy($fileStorage);
        $result   = $strategy->url('org-units/1/file.pdf');

        $this->assertSame('https://example.com/file.pdf', $result);
    }

    // ── OrganizationUnitAttachmentResource — uses strategy, not FileStorage ──

    public function test_org_unit_attachment_resource_uses_strategy_interface_not_file_storage(): void
    {
        $rc     = new \ReflectionClass(OrganizationUnitAttachmentResource::class);
        $source = file_get_contents($rc->getFileName());

        $this->assertStringContainsString(
            'AttachmentStorageStrategyInterface',
            $source,
            'OrganizationUnitAttachmentResource must resolve the URL via AttachmentStorageStrategyInterface.'
        );
        $this->assertStringNotContainsString(
            'FileStorageServiceInterface',
            $source,
            'OrganizationUnitAttachmentResource must not couple to FileStorageServiceInterface directly.'
        );
    }

    // ── OrganizationUnitAttachmentData DTO ────────────────────────────────────

    public function test_org_unit_attachment_data_dto_class_exists(): void
    {
        $this->assertTrue(class_exists(OrganizationUnitAttachmentData::class));
    }

    public function test_org_unit_attachment_data_dto_extends_base_dto(): void
    {
        $this->assertTrue(
            is_subclass_of(OrganizationUnitAttachmentData::class, \Modules\Core\Application\DTOs\BaseDto::class),
            'OrganizationUnitAttachmentData must extend BaseDto.'
        );
    }

    public function test_org_unit_attachment_data_dto_has_required_properties(): void
    {
        $rc = new \ReflectionClass(OrganizationUnitAttachmentData::class);

        foreach (['organization_unit_id', 'tenant_id', 'name', 'file_path', 'mime_type', 'size'] as $prop) {
            $this->assertTrue(
                $rc->hasProperty($prop),
                "OrganizationUnitAttachmentData must declare the '$prop' property."
            );
        }
    }

    public function test_org_unit_attachment_data_dto_has_nullable_optional_fields(): void
    {
        $dto = OrganizationUnitAttachmentData::fromArray([
            'organization_unit_id' => 1,
            'tenant_id'            => 1,
            'name'                 => 'file.pdf',
            'file_path'            => 'org-units/1/file.pdf',
            'mime_type'            => 'application/pdf',
            'size'                 => 1024,
        ]);

        $this->assertNull($dto->type);
        $this->assertNull($dto->metadata);
    }

    public function test_org_unit_attachment_data_dto_fill_sets_all_fields(): void
    {
        $dto = OrganizationUnitAttachmentData::fromArray([
            'organization_unit_id' => 5,
            'tenant_id'            => 2,
            'name'                 => 'report.pdf',
            'file_path'            => 'org-units/5/report.pdf',
            'mime_type'            => 'application/pdf',
            'size'                 => 2048,
            'type'                 => 'report',
            'metadata'             => ['author' => 'test'],
        ]);

        $this->assertSame(5, $dto->organization_unit_id);
        $this->assertSame(2, $dto->tenant_id);
        $this->assertSame('report.pdf', $dto->name);
        $this->assertSame('org-units/5/report.pdf', $dto->file_path);
        $this->assertSame('application/pdf', $dto->mime_type);
        $this->assertSame(2048, $dto->size);
        $this->assertSame('report', $dto->type);
        $this->assertSame(['author' => 'test'], $dto->metadata);
    }

    // ── UploadOrganizationUnitAttachmentService — uses DTO ───────────────────

    public function test_upload_org_unit_attachment_service_uses_attachment_data_dto(): void
    {
        $rc     = new \ReflectionClass(UploadOrganizationUnitAttachmentService::class);
        $source = file_get_contents($rc->getFileName());

        $this->assertStringContainsString(
            'OrganizationUnitAttachmentData',
            $source,
            'UploadOrganizationUnitAttachmentService must use OrganizationUnitAttachmentData DTO.'
        );
    }

    // ── BulkUploadOrganizationUnitAttachmentsService — uses DTO ──────────────

    public function test_bulk_upload_org_unit_attachments_service_uses_attachment_data_dto(): void
    {
        $rc     = new \ReflectionClass(BulkUploadOrganizationUnitAttachmentsService::class);
        $source = file_get_contents($rc->getFileName());

        $this->assertStringContainsString(
            'OrganizationUnitAttachmentData',
            $source,
            'BulkUploadOrganizationUnitAttachmentsService must use OrganizationUnitAttachmentData DTO.'
        );
    }

    // ── ReplaceOrganizationUnitAttachmentServiceInterface ────────────────────

    public function test_replace_org_unit_attachment_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(ReplaceOrganizationUnitAttachmentServiceInterface::class));
    }

    public function test_replace_org_unit_attachment_service_interface_extends_write_service(): void
    {
        $this->assertTrue(
            is_subclass_of(
                ReplaceOrganizationUnitAttachmentServiceInterface::class,
                \Modules\Core\Application\Contracts\WriteServiceInterface::class
            ),
            'ReplaceOrganizationUnitAttachmentServiceInterface must extend WriteServiceInterface.'
        );
    }

    // ── ReplaceOrganizationUnitAttachmentService ──────────────────────────────

    public function test_replace_org_unit_attachment_service_class_exists(): void
    {
        $this->assertTrue(class_exists(ReplaceOrganizationUnitAttachmentService::class));
    }

    public function test_replace_org_unit_attachment_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(
                ReplaceOrganizationUnitAttachmentService::class,
                ReplaceOrganizationUnitAttachmentServiceInterface::class
            ),
            'ReplaceOrganizationUnitAttachmentService must implement ReplaceOrganizationUnitAttachmentServiceInterface.'
        );
    }

    public function test_replace_org_unit_attachment_service_injects_storage_strategy(): void
    {
        $rc         = new \ReflectionClass(ReplaceOrganizationUnitAttachmentService::class);
        $params     = $rc->getConstructor()->getParameters();
        $paramTypes = array_map(fn ($p) => $p->getType()?->getName(), $params);

        $this->assertContains(
            AttachmentStorageStrategyInterface::class,
            $paramTypes,
            'ReplaceOrganizationUnitAttachmentService must inject AttachmentStorageStrategyInterface.'
        );
    }

    public function test_replace_org_unit_attachment_service_throws_when_not_found(): void
    {
        $this->expectException(AttachmentNotFoundException::class);

        $attachRepo = $this->createMock(OrganizationUnitAttachmentRepositoryInterface::class);
        $attachRepo->method('find')->willReturn(null);

        $strategy = $this->createMock(AttachmentStorageStrategyInterface::class);

        $service = new ReplaceOrganizationUnitAttachmentService($attachRepo, $strategy);
        $method  = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);

        $file = $this->createMock(UploadedFile::class);
        $method->invoke($service, ['attachment_id' => 99, 'file' => $file]);
    }

    public function test_replace_org_unit_attachment_service_deletes_old_file_and_stores_new(): void
    {
        $existing = $this->createTestAttachment(7, 1, 2);

        $attachRepo = $this->createMock(OrganizationUnitAttachmentRepositoryInterface::class);
        $attachRepo->method('find')->with(7)->willReturn($existing);
        $attachRepo->expects($this->once())->method('save')->willReturn($existing);

        $strategy = $this->createMock(AttachmentStorageStrategyInterface::class);
        $strategy->expects($this->once())->method('delete')->with($existing->getFilePath());
        $strategy->expects($this->once())->method('store')->willReturn('org-units/1/new-file.pdf');

        $file = $this->createMock(UploadedFile::class);
        $file->method('getClientOriginalName')->willReturn('new-file.pdf');
        $file->method('getMimeType')->willReturn('application/pdf');
        $file->method('getSize')->willReturn(2048);

        $service = new ReplaceOrganizationUnitAttachmentService($attachRepo, $strategy);
        $method  = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);

        $result = $method->invoke($service, ['attachment_id' => 7, 'file' => $file]);

        $this->assertInstanceOf(OrganizationUnitAttachment::class, $result);
    }

    public function test_replace_org_unit_attachment_service_preserves_uuid(): void
    {
        $existing = $this->createTestAttachment(8, 1, 2);
        $origUuid = $existing->getUuid();

        $attachRepo = $this->createMock(OrganizationUnitAttachmentRepositoryInterface::class);
        $attachRepo->method('find')->with(8)->willReturn($existing);

        $savedEntity = null;
        $attachRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (OrganizationUnitAttachment $a) use (&$savedEntity): OrganizationUnitAttachment {
                $savedEntity = $a;

                return $a;
            });

        $strategy = $this->createMock(AttachmentStorageStrategyInterface::class);
        $strategy->method('delete')->willReturn(true);
        $strategy->method('store')->willReturn('org-units/1/replaced.pdf');

        $file = $this->createMock(UploadedFile::class);
        $file->method('getClientOriginalName')->willReturn('replaced.pdf');
        $file->method('getMimeType')->willReturn('application/pdf');
        $file->method('getSize')->willReturn(512);

        $service = new ReplaceOrganizationUnitAttachmentService($attachRepo, $strategy);
        $method  = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, ['attachment_id' => 8, 'file' => $file]);

        $this->assertSame($origUuid, $savedEntity->getUuid(),
            'ReplaceOrganizationUnitAttachmentService must preserve the original UUID.');
    }

    public function test_replace_org_unit_attachment_service_uses_attachment_data_dto(): void
    {
        $rc     = new \ReflectionClass(ReplaceOrganizationUnitAttachmentService::class);
        $source = file_get_contents($rc->getFileName());

        $this->assertStringContainsString(
            'OrganizationUnitAttachmentData',
            $source,
            'ReplaceOrganizationUnitAttachmentService must use OrganizationUnitAttachmentData DTO.'
        );
    }

    // ── OrganizationUnitAttachmentController — replace action ────────────────

    public function test_org_unit_attachment_controller_has_replace_method(): void
    {
        $rc = new \ReflectionClass(OrganizationUnitAttachmentController::class);

        $this->assertTrue(
            $rc->hasMethod('replace'),
            'OrganizationUnitAttachmentController must declare a replace() action.'
        );
    }

    public function test_org_unit_attachment_controller_injects_replace_service(): void
    {
        $rc         = new \ReflectionClass(OrganizationUnitAttachmentController::class);
        $params     = $rc->getConstructor()->getParameters();
        $paramTypes = array_map(fn ($p) => $p->getType()?->getName(), $params);

        $this->assertContains(
            ReplaceOrganizationUnitAttachmentServiceInterface::class,
            $paramTypes,
            'OrganizationUnitAttachmentController must inject ReplaceOrganizationUnitAttachmentServiceInterface.'
        );
    }

    // ── ServiceProvider — registers ReplaceOrganizationUnitAttachmentService ─

    public function test_org_unit_service_provider_registers_replace_attachment_service(): void
    {
        $rc       = new \ReflectionClass(OrganizationUnitServiceProvider::class);
        $method   = $rc->getMethod('register');
        $filename = $rc->getFileName();
        $start    = $method->getStartLine();
        $end      = $method->getEndLine();
        $lines    = array_slice(file($filename), $start - 1, $end - $start + 1);
        $body     = implode('', $lines);

        $this->assertStringContainsString('ReplaceOrganizationUnitAttachmentServiceInterface', $body,
            'ServiceProvider must bind ReplaceOrganizationUnitAttachmentServiceInterface.');
    }

    // ── Routes — replace route ────────────────────────────────────────────────

    public function test_routes_file_has_replace_route(): void
    {
        $routesFile = __DIR__.'/../../app/Modules/OrganizationUnit/routes/api.php';
        $content    = file_get_contents($routesFile);

        $this->assertStringContainsString('replace', $content,
            'Routes must include a replace endpoint for attachments.');
    }

    // ── OrganizationUnitAttachment entity — updateDetails() ──────────────────

    public function test_org_unit_attachment_entity_has_update_details_method(): void
    {
        $rc = new \ReflectionClass(OrganizationUnitAttachment::class);

        $this->assertTrue(
            $rc->hasMethod('updateDetails'),
            'OrganizationUnitAttachment must declare updateDetails(type, metadata).'
        );
    }

    public function test_org_unit_attachment_entity_update_details_changes_type_and_metadata(): void
    {
        $attachment = $this->createTestAttachment(1, 1, 1);

        $attachment->updateDetails('invoice', ['ref' => '123']);

        $this->assertSame('invoice', $attachment->getType());
        $this->assertSame(['ref' => '123'], $attachment->getMetadata());
    }

    public function test_org_unit_attachment_entity_update_details_clears_type_and_metadata(): void
    {
        $attachment = $this->createTestAttachment(1, 1, 1);
        $attachment->updateDetails('old-type', ['key' => 'val']);

        $attachment->updateDetails(null, null);

        $this->assertNull($attachment->getType());
        $this->assertNull($attachment->getMetadata());
    }

    // ── UpdateOrganizationUnitAttachmentData DTO ──────────────────────────────

    public function test_update_org_unit_attachment_data_dto_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdateOrganizationUnitAttachmentData::class));
    }

    public function test_update_org_unit_attachment_data_dto_is_provided_returns_true_for_present_fields(): void
    {
        $dto = UpdateOrganizationUnitAttachmentData::fromArray(['type' => 'invoice']);

        $this->assertTrue($dto->isProvided('type'));
        $this->assertFalse($dto->isProvided('metadata'));
    }

    public function test_update_org_unit_attachment_data_dto_to_array_only_returns_provided_keys(): void
    {
        $dto = UpdateOrganizationUnitAttachmentData::fromArray(['metadata' => ['a' => 1]]);

        $arr = $dto->toArray();
        $this->assertArrayHasKey('metadata', $arr);
        $this->assertArrayNotHasKey('type', $arr);
    }

    public function test_update_org_unit_attachment_data_dto_accepts_null_values(): void
    {
        $dto = UpdateOrganizationUnitAttachmentData::fromArray(['type' => null, 'metadata' => null]);

        $this->assertTrue($dto->isProvided('type'));
        $this->assertTrue($dto->isProvided('metadata'));
        $this->assertNull($dto->type);
        $this->assertNull($dto->metadata);
    }

    public function test_update_org_unit_attachment_data_dto_does_not_have_file_fields(): void
    {
        $rc = new \ReflectionClass(UpdateOrganizationUnitAttachmentData::class);

        $this->assertFalse($rc->hasProperty('file_path'),  'UpdateOrganizationUnitAttachmentData must not have file_path.');
        $this->assertFalse($rc->hasProperty('mime_type'),  'UpdateOrganizationUnitAttachmentData must not have mime_type.');
        $this->assertFalse($rc->hasProperty('size'),       'UpdateOrganizationUnitAttachmentData must not have size.');
    }

    // ── UpdateOrganizationUnitAttachmentRequest ───────────────────────────────

    public function test_update_org_unit_attachment_request_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdateOrganizationUnitAttachmentRequest::class));
    }

    public function test_update_org_unit_attachment_request_has_type_and_metadata_rules(): void
    {
        $request = new UpdateOrganizationUnitAttachmentRequest;
        $rules   = $request->rules();

        $this->assertArrayHasKey('type', $rules);
        $this->assertArrayHasKey('metadata', $rules);
    }

    public function test_update_org_unit_attachment_request_type_rule_is_nullable(): void
    {
        $request = new UpdateOrganizationUnitAttachmentRequest;
        $rules   = $request->rules();

        $this->assertStringContainsString('nullable', $rules['type'],
            'type rule must allow null.');
    }

    public function test_update_org_unit_attachment_request_has_with_validator_method(): void
    {
        $rc = new \ReflectionClass(UpdateOrganizationUnitAttachmentRequest::class);

        $this->assertTrue(
            $rc->hasMethod('withValidator'),
            'UpdateOrganizationUnitAttachmentRequest must have a withValidator() method.'
        );
    }

    // ── UpdateOrganizationUnitAttachmentServiceInterface ─────────────────────

    public function test_update_org_unit_attachment_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(UpdateOrganizationUnitAttachmentServiceInterface::class));
    }

    public function test_update_org_unit_attachment_service_interface_extends_write_service(): void
    {
        $this->assertTrue(
            is_subclass_of(
                UpdateOrganizationUnitAttachmentServiceInterface::class,
                \Modules\Core\Application\Contracts\WriteServiceInterface::class
            ),
            'UpdateOrganizationUnitAttachmentServiceInterface must extend WriteServiceInterface.'
        );
    }

    // ── UpdateOrganizationUnitAttachmentService ───────────────────────────────

    public function test_update_org_unit_attachment_service_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdateOrganizationUnitAttachmentService::class));
    }

    public function test_update_org_unit_attachment_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(
                UpdateOrganizationUnitAttachmentService::class,
                UpdateOrganizationUnitAttachmentServiceInterface::class
            ),
            'UpdateOrganizationUnitAttachmentService must implement UpdateOrganizationUnitAttachmentServiceInterface.'
        );
    }

    public function test_update_org_unit_attachment_service_uses_dto(): void
    {
        $rc     = new \ReflectionClass(UpdateOrganizationUnitAttachmentService::class);
        $source = file_get_contents($rc->getFileName());

        $this->assertStringContainsString(
            'UpdateOrganizationUnitAttachmentData',
            $source,
            'UpdateOrganizationUnitAttachmentService must use UpdateOrganizationUnitAttachmentData DTO.'
        );
    }

    public function test_update_org_unit_attachment_service_throws_when_not_found(): void
    {
        $this->expectException(AttachmentNotFoundException::class);

        $attachRepo = $this->createMock(OrganizationUnitAttachmentRepositoryInterface::class);
        $attachRepo->method('find')->willReturn(null);

        $service = new UpdateOrganizationUnitAttachmentService($attachRepo);
        $method  = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, ['attachment_id' => 99]);
    }

    public function test_update_org_unit_attachment_service_updates_type_preserves_metadata(): void
    {
        $existing = $this->createTestAttachment(10, 1, 2);
        $existing->updateDetails('old', ['a' => 1]);

        $saved = null;
        $attachRepo = $this->createMock(OrganizationUnitAttachmentRepositoryInterface::class);
        $attachRepo->method('find')->with(10)->willReturn($existing);
        $attachRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (OrganizationUnitAttachment $a) use (&$saved): OrganizationUnitAttachment {
                $saved = $a;

                return $a;
            });

        $service = new UpdateOrganizationUnitAttachmentService($attachRepo);
        $method  = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, ['attachment_id' => 10, 'type' => 'new-type']);

        $this->assertSame('new-type', $saved->getType());
        $this->assertSame(['a' => 1], $saved->getMetadata(), 'Metadata must be preserved when not provided.');
    }

    public function test_update_org_unit_attachment_service_updates_metadata_preserves_type(): void
    {
        $existing = $this->createTestAttachment(11, 1, 2);
        $existing->updateDetails('keep-type', ['old' => true]);

        $saved = null;
        $attachRepo = $this->createMock(OrganizationUnitAttachmentRepositoryInterface::class);
        $attachRepo->method('find')->with(11)->willReturn($existing);
        $attachRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (OrganizationUnitAttachment $a) use (&$saved): OrganizationUnitAttachment {
                $saved = $a;

                return $a;
            });

        $service = new UpdateOrganizationUnitAttachmentService($attachRepo);
        $method  = new \ReflectionMethod($service, 'handle');
        $method->setAccessible(true);
        $method->invoke($service, ['attachment_id' => 11, 'metadata' => ['new' => true]]);

        $this->assertSame('keep-type', $saved->getType(), 'Type must be preserved when not provided.');
        $this->assertSame(['new' => true], $saved->getMetadata());
    }

    // ── OrganizationUnitAttachmentController — update action ─────────────────

    public function test_org_unit_attachment_controller_has_update_method(): void
    {
        $rc = new \ReflectionClass(OrganizationUnitAttachmentController::class);

        $this->assertTrue(
            $rc->hasMethod('update'),
            'OrganizationUnitAttachmentController must declare an update() action.'
        );
    }

    public function test_org_unit_attachment_controller_injects_update_attachment_service(): void
    {
        $rc         = new \ReflectionClass(OrganizationUnitAttachmentController::class);
        $params     = $rc->getConstructor()->getParameters();
        $paramTypes = array_map(fn ($p) => $p->getType()?->getName(), $params);

        $this->assertContains(
            UpdateOrganizationUnitAttachmentServiceInterface::class,
            $paramTypes,
            'OrganizationUnitAttachmentController must inject UpdateOrganizationUnitAttachmentServiceInterface.'
        );
    }

    // ── ServiceProvider — registers UpdateOrganizationUnitAttachmentService ──

    public function test_org_unit_service_provider_registers_update_attachment_service(): void
    {
        $rc       = new \ReflectionClass(OrganizationUnitServiceProvider::class);
        $method   = $rc->getMethod('register');
        $filename = $rc->getFileName();
        $start    = $method->getStartLine();
        $end      = $method->getEndLine();
        $lines    = array_slice(file($filename), $start - 1, $end - $start + 1);
        $body     = implode('', $lines);

        $this->assertStringContainsString('UpdateOrganizationUnitAttachmentServiceInterface', $body,
            'ServiceProvider must bind UpdateOrganizationUnitAttachmentServiceInterface.');
    }

    // ── Routes — PATCH update route ───────────────────────────────────────────

    public function test_routes_file_has_patch_update_attachment_route(): void
    {
        $routesFile = __DIR__.'/../../app/Modules/OrganizationUnit/routes/api.php';
        $content    = file_get_contents($routesFile);

        $this->assertStringContainsString("Route::patch('org-units/{unit}/attachments/{attachment}'", $content,
            'Routes must include a PATCH update endpoint for attachments.');
    }
}
