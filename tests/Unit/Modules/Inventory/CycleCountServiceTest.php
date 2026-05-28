<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Inventory;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\StockLedgerServiceInterface;
use Modules\Inventory\Application\Repositories\CycleCountHeaderRepositoryInterface;
use Modules\Inventory\Application\Repositories\CycleCountLineRepositoryInterface;
use Modules\Inventory\Application\Services\CycleCountService;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\UOM\Application\Contracts\Services\UomConversionServiceInterface;
use PHPUnit\Framework\TestCase;

final class CycleCountServiceTest extends TestCase
{
    public function testItCreatesCycleCountLinesWithComputedVariance(): void
    {
        $headerRepository = $this->createMock(CycleCountHeaderRepositoryInterface::class);
        $lineRepository = $this->createMock(CycleCountLineRepositoryInterface::class);
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

        $headerRepository
            ->method('transaction')
            ->willReturnCallback(static fn (callable $callback) => $callback());

        $headerRepository
            ->expects(self::once())
            ->method('create')
            ->with(self::callback(static function (array $payload): bool {
                return ($payload['tenant_id'] ?? null) === 1
                    && ($payload['warehouse_id'] ?? null) === 10
                    && ($payload['status'] ?? null) === 'draft';
            }))
            ->willReturn(new DataRecord(['id' => 600]));

        $lineRepository
            ->expects(self::once())
            ->method('create')
            ->with(self::callback(static function (array $payload): bool {
                return ($payload['count_header_id'] ?? null) === 600
                    && ($payload['item_id'] ?? null) === 100
                    && ($payload['transaction_uom_id'] ?? null) === 2
                    && ($payload['base_uom_id'] ?? null) === 1
                    && (float) ($payload['system_qty'] ?? 0) === 10.0
                    && (float) ($payload['counted_qty'] ?? 0) === 8.0
                    && (float) ($payload['variance_qty'] ?? 0) === -2.0
                    && (float) ($payload['base_system_qty'] ?? 0) === 20.0
                    && (float) ($payload['base_counted_qty'] ?? 0) === 16.0
                    && (float) ($payload['base_variance_qty'] ?? 0) === -4.0;
            }))
            ->willReturn(new DataRecord(['id' => 601]));

        $service = new CycleCountService(
            $headerRepository,
            $lineRepository,
            $itemRepository,
            $uomConversionService,
            $stockLedgerService,
        );

        $result = $service->createCount([
            'tenant_id' => 1,
            'warehouse_id' => 10,
            'lines' => [[
                'item_id' => 100,
                'uom_id' => 2,
                'system_qty' => 10,
                'counted_qty' => 8,
            ]],
        ]);

        self::assertTrue(
            $result->isSuccess(),
            $result->isFailure() ? $result->errorOrFail()->message : 'Expected success.',
        );
    }

    public function testItPostsCompletedCycleCountVarianceThroughLedgerOnUpdate(): void
    {
        $headerRepository = $this->createMock(CycleCountHeaderRepositoryInterface::class);
        $lineRepository = $this->createMock(CycleCountLineRepositoryInterface::class);
        $itemRepository = $this->createMock(ItemRepositoryInterface::class);
        $uomConversionService = $this->createMock(UomConversionServiceInterface::class);
        $stockLedgerService = $this->createMock(StockLedgerServiceInterface::class);

        $existing = new DataRecord([
            'id' => 600,
            'tenant_id' => 1,
            'warehouse_id' => 10,
            'status' => 'draft',
            'approved_by_user_id' => 7,
            'approved_at' => '2026-05-28 13:00:00',
        ]);
        $updated = new DataRecord([
            'id' => 600,
            'tenant_id' => 1,
            'warehouse_id' => 10,
            'status' => 'completed',
            'approved_by_user_id' => 7,
            'approved_at' => '2026-05-28 13:00:00',
        ]);
        $line = new DataRecord([
            'id' => 601,
            'count_header_id' => 600,
            'item_id' => 100,
            'transaction_uom_id' => 2,
            'variance_qty' => 4.0,
            'adjustment_movement_id' => null,
            'unit_cost' => 3.5,
        ]);

        $headerRepository
            ->method('findById')
            ->with(600)
            ->willReturn($existing);

        $headerRepository
            ->method('transaction')
            ->willReturnCallback(static fn (callable $callback) => $callback());

        $updateCalls = [];
        $headerRepository
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

        $lineRepository
            ->expects(self::exactly(2))
            ->method('list')
            ->with(['count_header_id' => 600])
            ->willReturn([$line]);

        $stockLedgerService
            ->expects(self::once())
            ->method('recordMovement')
            ->with(self::callback(static function (array $payload): bool {
                return ($payload['item_id'] ?? null) === 100
                    && ($payload['direction'] ?? null) === 'IN'
                    && (float) ($payload['quantity'] ?? 0) === 4.0
                    && ($payload['movement_type'] ?? null) === 'CYCLE_COUNT_ADJUSTMENT'
                    && ($payload['source_type'] ?? null) === 'cycle_count'
                    && ($payload['source_id'] ?? null) === 600
                    && ($payload['source_line_id'] ?? null) === 601;
            }))
            ->willReturn(Result::success(new DataRecord(['id' => 9002])));

        $lineRepository
            ->expects(self::once())
            ->method('update')
            ->with(601, ['adjustment_movement_id' => 9002])
            ->willReturn(new DataRecord(['id' => 601]));

        $service = new CycleCountService(
            $headerRepository,
            $lineRepository,
            $itemRepository,
            $uomConversionService,
            $stockLedgerService,
        );

        $result = $service->updateCount(600, ['status' => 'completed']);

        self::assertTrue(
            $result->isSuccess(),
            $result->isFailure() ? $result->errorOrFail()->message : 'Expected success.',
        );
        self::assertCount(2, $updateCalls);
        self::assertSame([600, ['status' => 'completed']], $updateCalls[0]);
        self::assertSame(600, $updateCalls[1][0]);
        self::assertSame('completed', $updateCalls[1][1]['status'] ?? null);
        self::assertSame('2026-05-28 13:00:00', $updateCalls[1][1]['approved_at'] ?? null);
    }
}
