<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Inventory;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Inventory\Application\Repositories\CycleCountLineRepositoryInterface;
use Modules\Inventory\Application\Repositories\StockAdjustmentLineRepositoryInterface;
use Modules\Inventory\Application\Repositories\StockTransferLineRepositoryInterface;
use Modules\Inventory\Application\Services\CycleCountLineService;
use Modules\Inventory\Application\Services\StockAdjustmentLineService;
use Modules\Inventory\Application\Services\StockTransferLineService;
use PHPUnit\Framework\TestCase;

final class LineServicesGuardTest extends TestCase
{
    public function testAdjustmentLineRejectsMutationAfterMovementLinked(): void
    {
        $repository = $this->createMock(StockAdjustmentLineRepositoryInterface::class);
        $repository
            ->method('findById')
            ->with(1)
            ->willReturn(new DataRecord([
                'id' => 1,
                'adjustment_movement_id' => 10,
                'tenant_id' => 1,
                'stock_adjustment_id' => 2,
                'item_id' => 3,
                'warehouse_id' => 4,
                'adjustment_quantity' => 1,
            ]));

        $repository->expects(self::never())->method('update');

        $service = new StockAdjustmentLineService($repository);
        $result = $service->updateLine(1, ['item_id' => 99]);

        self::assertTrue($result->isFailure());
    }

    public function testCycleCountLineRejectsMutationAfterMovementLinked(): void
    {
        $repository = $this->createMock(CycleCountLineRepositoryInterface::class);
        $repository
            ->method('findById')
            ->with(1)
            ->willReturn(new DataRecord([
                'id' => 1,
                'adjustment_movement_id' => 10,
                'tenant_id' => 1,
                'count_header_id' => 2,
                'item_id' => 3,
                'system_qty' => 1,
                'counted_qty' => 1,
            ]));

        $repository->expects(self::never())->method('update');

        $service = new CycleCountLineService($repository);
        $result = $service->updateLine(1, ['item_id' => 99]);

        self::assertTrue($result->isFailure());
    }

    public function testTransferLineRejectsMutationAfterMovementLinked(): void
    {
        $repository = $this->createMock(StockTransferLineRepositoryInterface::class);
        $repository
            ->method('findById')
            ->with(1)
            ->willReturn(new DataRecord([
                'id' => 1,
                'outgoing_movement_id' => 10,
                'tenant_id' => 1,
                'stock_transfer_id' => 2,
                'item_id' => 3,
                'quantity' => 1,
            ]));

        $repository->expects(self::never())->method('update');

        $service = new StockTransferLineService($repository);
        $result = $service->updateLine(1, ['item_id' => 99]);

        self::assertTrue($result->isFailure());
    }
}
