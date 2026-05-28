<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Inventory;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Inventory\Application\Repositories\BatchRepositoryInterface;
use Modules\Inventory\Application\Services\BatchService;
use PHPUnit\Framework\TestCase;

final class BatchServiceTest extends TestCase
{
    public function testItCreatesBatchWithDefaultStatusAndRowVersion(): void
    {
        $repository = $this->createMock(BatchRepositoryInterface::class);

        $repository
            ->expects(self::once())
            ->method('create')
            ->with(self::callback(static function (array $payload): bool {
                return ($payload['tenant_id'] ?? null) === 1
                    && ($payload['item_id'] ?? null) === 100
                    && ($payload['batch_number'] ?? null) === 'BATCH-001'
                    && ($payload['status'] ?? null) === 'active'
                    && ($payload['row_version'] ?? null) === 1;
            }))
            ->willReturn(new DataRecord(['id' => 10]));

        $service = new BatchService($repository);

        $result = $service->createBatch([
            'tenant_id' => 1,
            'item_id' => 100,
            'batch_number' => 'BATCH-001',
        ]);

        self::assertTrue($result->isSuccess());
    }

    public function testItRejectsInactiveBatchStructuralMutations(): void
    {
        $repository = $this->createMock(BatchRepositoryInterface::class);
        $repository
            ->method('findById')
            ->with(10)
            ->willReturn(new DataRecord([
                'id' => 10,
                'tenant_id' => 1,
                'item_id' => 100,
                'batch_number' => 'BATCH-001',
                'status' => 'inactive',
            ]));

        $repository->expects(self::never())->method('update');

        $service = new BatchService($repository);

        $result = $service->updateBatch(10, ['batch_number' => 'BATCH-002']);

        self::assertTrue($result->isFailure());
    }
}
