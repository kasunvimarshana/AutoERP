<?php

declare(strict_types=1);

namespace Tests\Unit\Inventory;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Inventory\Application\Services\CreateBatchService;
use Modules\Inventory\Application\Services\DeleteBatchService;
use Modules\Inventory\Application\Services\FindBatchService;
use Modules\Inventory\Application\Services\UpdateBatchService;
use Modules\Inventory\Domain\Entities\Batch;
use Modules\Inventory\Domain\Exceptions\BatchNotFoundException;
use Modules\Inventory\Domain\RepositoryInterfaces\BatchRepositoryInterface;
use Modules\Product\Application\Contracts\RefreshProductSearchProjectionServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class BatchServiceTest extends TestCase
{
    /** @var BatchRepositoryInterface&MockObject */
    private BatchRepositoryInterface $repository;

    /** @var RefreshProductSearchProjectionServiceInterface&MockObject */
    private RefreshProductSearchProjectionServiceInterface $refreshService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository     = $this->createMock(BatchRepositoryInterface::class);
        $this->refreshService = $this->createMock(RefreshProductSearchProjectionServiceInterface::class);
    }

    public function test_create_batch_service_maps_payload_and_saves(): void
    {
        $service = new CreateBatchService($this->repository, $this->refreshService);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (mixed $batch): bool {
                if (! $batch instanceof Batch) {
                    return false;
                }

                return $batch->getTenantId() === 5
                    && $batch->getProductId() === 10
                    && $batch->getBatchNumber() === 'BATCH-001'
                    && $batch->getStatus() === 'active';
            }))
            ->willReturn($this->buildBatch(1));

        $this->refreshService
            ->expects($this->once())
            ->method('execute')
            ->with(5, 10);

        $result = $service->execute([
            'tenant_id'    => 5,
            'product_id'   => 10,
            'batch_number' => 'BATCH-001',
            'lot_number'   => 'LOT-A',
            'status'       => 'active',
        ]);

        $this->assertInstanceOf(Batch::class, $result);
        $this->assertSame(1, $result->getId());
    }

    public function test_find_batch_service_list_delegates_to_repository(): void
    {
        $service   = new FindBatchService($this->repository);
        $paginator = $this->createMock(LengthAwarePaginator::class);

        $this->repository
            ->expects($this->once())
            ->method('findByTenant')
            ->with(5, $this->anything(), 15, 1, 'id')
            ->willReturn($paginator);

        $result = $service->list(
            filters: ['tenant_id' => 5, 'status' => 'active'],
            perPage: 15,
            page: 1,
            sort: 'id',
        );

        $this->assertSame($paginator, $result);
    }

    public function test_find_batch_service_find_by_id_delegates_to_repository(): void
    {
        $service = new FindBatchService($this->repository);
        $batch   = $this->buildBatch(7);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(7)
            ->willReturn($batch);

        $result = $service->findById(7);

        $this->assertSame($batch, $result);
    }

    public function test_update_batch_service_throws_when_batch_missing(): void
    {
        $service = new UpdateBatchService($this->repository, $this->refreshService);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->refreshService->expects($this->never())->method('execute');

        $this->expectException(BatchNotFoundException::class);

        $service->execute([
            'id'           => 999,
            'tenant_id'    => 5,
            'product_id'   => 10,
            'batch_number' => 'BATCH-001',
            'status'       => 'active',
        ]);
    }

    public function test_update_batch_service_saves_and_triggers_refresh(): void
    {
        $service      = new UpdateBatchService($this->repository, $this->refreshService);
        $existingBatch = $this->buildBatch(3);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(3)
            ->willReturn($existingBatch);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->willReturn($existingBatch);

        $this->refreshService
            ->expects($this->once())
            ->method('execute')
            ->with(5, 10);

        $service->execute([
            'id'           => 3,
            'batch_number' => 'BATCH-001-UPDATED',
            'status'       => 'quarantine',
        ]);
    }

    public function test_delete_batch_service_throws_when_batch_missing(): void
    {
        $service = new DeleteBatchService($this->repository, $this->refreshService);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(555)
            ->willReturn(null);

        $this->refreshService->expects($this->never())->method('execute');

        $this->expectException(BatchNotFoundException::class);

        $service->execute(['id' => 555]);
    }

    public function test_delete_batch_service_deletes_and_triggers_refresh(): void
    {
        $service = new DeleteBatchService($this->repository, $this->refreshService);
        $batch   = $this->buildBatch(4);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(4)
            ->willReturn($batch);

        $this->repository
            ->expects($this->once())
            ->method('delete')
            ->with(4)
            ->willReturn(true);

        $this->refreshService
            ->expects($this->once())
            ->method('execute')
            ->with(5, 10);

        $result = $service->execute(['id' => 4]);

        $this->assertTrue($result);
    }

    private function buildBatch(int $id): Batch
    {
        return new Batch(
            tenantId:        5,
            productId:       10,
            variantId:       null,
            batchNumber:     'BATCH-001',
            lotNumber:       'LOT-A',
            manufactureDate: null,
            expiryDate:      null,
            receivedDate:    null,
            supplierId:      null,
            status:          'active',
            notes:           null,
            metadata:        null,
            salesPrice:      null,
            id:              $id,
        );
    }
}
