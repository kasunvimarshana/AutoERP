<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Inventory;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\StockLedgerServiceInterface;
use Modules\Inventory\Application\Repositories\StockAdjustmentLineRepositoryInterface;
use Modules\Inventory\Application\Repositories\StockAdjustmentRepositoryInterface;
use Modules\Inventory\Application\Services\StockAdjustmentService;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\UOM\Application\Contracts\Services\UomConversionServiceInterface;
use PHPUnit\Framework\TestCase;

final class StockAdjustmentServiceTest extends TestCase
{
    public function testItCreatesValidatedAdjustmentWithNormalizedLines(): void
    {
        $adjustmentRepository = $this->createMock(StockAdjustmentRepositoryInterface::class);
        $adjustmentLineRepository = $this->createMock(StockAdjustmentLineRepositoryInterface::class);
        $itemRepository = $this->createMock(ItemRepositoryInterface::class);
        $uomConversionService = $this->createMock(UomConversionServiceInterface::class);
        $stockLedgerService = $this->createMock(StockLedgerServiceInterface::class);

        $itemRepository
            ->method('findByIdInTenant')
            ->with(100, 1)
            ->willReturn(new DataRecord([
                'id' => 100,
                'tenant_id' => 1,
                'base_uom_id' => 1,
                'is_stockable' => true,
            ]));

        $uomConversionService
            ->method('convert')
            ->willReturnCallback(static function (float $quantity): Result {
                return Result::success($quantity * 2);
            });

        $adjustmentRepository
            ->method('transaction')
            ->willReturnCallback(static fn (callable $callback) => $callback());

        $adjustmentRepository
            ->expects(self::once())
            ->method('create')
            ->with(self::callback(static function (array $payload): bool {
                return ($payload['tenant_id'] ?? null) === 1
                    && ($payload['warehouse_id'] ?? null) === 10
                    && ($payload['status'] ?? null) === 'DRAFT';
            }))
            ->willReturn(new DataRecord(['id' => 500]));

        $adjustmentLineRepository
            ->expects(self::once())
            ->method('create')
            ->with(self::callback(static function (array $payload): bool {
                return ($payload['stock_adjustment_id'] ?? null) === 500
                    && ($payload['item_id'] ?? null) === 100
                    && ($payload['transaction_uom_id'] ?? null) === 2
                    && ($payload['base_uom_id'] ?? null) === 1
                    && (float) ($payload['current_quantity'] ?? 0) === 10.0
                    && (float) ($payload['base_current_quantity'] ?? 0) === 20.0
                    && (float) ($payload['adjustment_quantity'] ?? 0) === 3.0
                    && (float) ($payload['base_adjustment_quantity'] ?? 0) === 6.0
                    && (float) ($payload['resulting_quantity'] ?? 0) === 13.0
                    && (float) ($payload['base_resulting_quantity'] ?? 0) === 26.0;
            }))
            ->willReturn(new DataRecord(['id' => 501]));

        $service = new StockAdjustmentService(
            $adjustmentRepository,
            $adjustmentLineRepository,
            $itemRepository,
            $uomConversionService,
            $stockLedgerService,
        );

        $result = $service->createAdjustment([
            'tenant_id' => 1,
            'warehouse_id' => 10,
            'reference_number' => 'ADJ-0001',
            'lines' => [[
                'item_id' => 100,
                'uom_id' => 2,
                'current_quantity' => 10,
                'adjustment_quantity' => 3,
                'direction' => 'INCREASE',
            ]],
        ]);

        self::assertTrue(
            $result->isSuccess(),
            $result->isFailure() ? $result->errorOrFail()->message : 'Expected success.',
        );
    }

    public function testItPostsCompletedAdjustmentThroughLedgerOnUpdate(): void
    {
        $adjustmentRepository = $this->createMock(StockAdjustmentRepositoryInterface::class);
        $adjustmentLineRepository = $this->createMock(StockAdjustmentLineRepositoryInterface::class);
        $itemRepository = $this->createMock(ItemRepositoryInterface::class);
        $uomConversionService = $this->createMock(UomConversionServiceInterface::class);
        $stockLedgerService = $this->createMock(StockLedgerServiceInterface::class);

        $existing = new DataRecord([
            'id' => 500,
            'tenant_id' => 1,
            'warehouse_id' => 10,
            'status' => 'DRAFT',
            'approved_by' => 7,
            'approved_at' => '2026-05-28 12:00:00',
            'reason' => 'count variance',
        ]);
        $updated = new DataRecord([
            'id' => 500,
            'tenant_id' => 1,
            'warehouse_id' => 10,
            'status' => 'COMPLETED',
            'approved_by' => 7,
            'approved_at' => '2026-05-28 12:00:00',
            'reason' => 'count variance',
        ]);
        $line = new DataRecord([
            'id' => 501,
            'stock_adjustment_id' => 500,
            'item_id' => 100,
            'warehouse_id' => 10,
            'transaction_uom_id' => 2,
            'adjustment_quantity' => -3.0,
            'base_adjustment_quantity' => -6.0,
            'adjustment_movement_id' => null,
            'unit_cost' => 2.5,
        ]);

        $adjustmentRepository
            ->method('findById')
            ->with(500)
            ->willReturn($existing);

        $adjustmentRepository
            ->method('transaction')
            ->willReturnCallback(static fn (callable $callback) => $callback());

        $updateCalls = [];
        $adjustmentRepository
            ->expects(self::exactly(2))
            ->method('update')
            ->willReturnCallback(static function (
                int|string $id,
                array $payload,
            ) use (
                &$updateCalls,
                $updated,
            ): DataRecord {
                $updateCalls[] = [$id, $payload];

                return $updated;
            });

        $adjustmentLineRepository
            ->expects(self::exactly(2))
            ->method('list')
            ->with(['stock_adjustment_id' => 500])
            ->willReturn([$line]);

        $stockLedgerService
            ->expects(self::once())
            ->method('recordMovement')
            ->with(self::callback(static function (array $payload): bool {
                return ($payload['item_id'] ?? null) === 100
                    && ($payload['direction'] ?? null) === 'OUT'
                    && (float) ($payload['quantity'] ?? 0) === 3.0
                    && ($payload['movement_type'] ?? null) === 'STOCK_ADJUSTMENT'
                    && ($payload['source_type'] ?? null) === 'stock_adjustment'
                    && ($payload['source_id'] ?? null) === 500
                    && ($payload['source_line_id'] ?? null) === 501;
            }))
            ->willReturn(Result::success(new DataRecord(['id' => 9001])));

        $adjustmentLineRepository
            ->expects(self::once())
            ->method('update')
            ->with(501, ['adjustment_movement_id' => 9001])
            ->willReturn(new DataRecord(['id' => 501]));

        $service = new StockAdjustmentService(
            $adjustmentRepository,
            $adjustmentLineRepository,
            $itemRepository,
            $uomConversionService,
            $stockLedgerService,
        );

        $result = $service->updateAdjustment(500, ['status' => 'COMPLETED']);

        self::assertTrue(
            $result->isSuccess(),
            $result->isFailure() ? $result->errorOrFail()->message : 'Expected success.',
        );
        self::assertCount(2, $updateCalls);
        self::assertSame([500, ['status' => 'COMPLETED']], $updateCalls[0]);
        self::assertSame(500, $updateCalls[1][0]);
        self::assertSame('COMPLETED', $updateCalls[1][1]['status'] ?? null);
        self::assertSame('2026-05-28 12:00:00', $updateCalls[1][1]['approved_at'] ?? null);
    }
}
