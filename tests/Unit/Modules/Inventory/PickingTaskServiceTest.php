<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Inventory;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\StockLedgerServiceInterface;
use Modules\Inventory\Application\Contracts\Services\StockReservationServiceInterface;
use Modules\Inventory\Application\Repositories\PickingTaskRepositoryInterface;
use Modules\Inventory\Application\Repositories\StockReservationRepositoryInterface;
use Modules\Inventory\Application\Services\PickingTaskService;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\UOM\Application\Contracts\Services\UomConversionServiceInterface;
use PHPUnit\Framework\TestCase;

final class PickingTaskServiceTest extends TestCase
{
    public function testItCreatesPickingTaskWithNormalizedBaseQuantities(): void
    {
        $pickingTaskRepository = $this->createMock(PickingTaskRepositoryInterface::class);
        $stockReservationRepository = $this->createMock(StockReservationRepositoryInterface::class);
        $itemRepository = $this->createMock(ItemRepositoryInterface::class);
        $uomConversionService = $this->createMock(UomConversionServiceInterface::class);
        $stockLedgerService = $this->createMock(StockLedgerServiceInterface::class);
        $stockReservationService = $this->createMock(StockReservationServiceInterface::class);

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
            ->willReturnOnConsecutiveCalls(Result::success(10.0), Result::success(4.0));

        $pickingTaskRepository
            ->method('transaction')
            ->willReturnCallback(static fn (callable $callback) => $callback());

        $pickingTaskRepository
            ->expects(self::once())
            ->method('create')
            ->with(self::callback(static function (array $payload): bool {
                return ($payload['tenant_id'] ?? null) === 1
                    && ($payload['item_id'] ?? null) === 100
                    && ($payload['source_warehouse_id'] ?? null) === 10
                    && ($payload['transaction_uom_id'] ?? null) === 2
                    && ($payload['base_uom_id'] ?? null) === 1
                    && (float) ($payload['reserved_quantity'] ?? 0) === 5.0
                    && (float) ($payload['picked_quantity'] ?? 0) === 2.0
                    && (float) ($payload['base_reserved_quantity'] ?? 0) === 10.0
                    && (float) ($payload['base_picked_quantity'] ?? 0) === 4.0
                        && array_key_exists('stock_movement_id', $payload)
                        && $payload['stock_movement_id'] === null;
            }))
            ->willReturn(new DataRecord(['id' => 801]));

        $service = new PickingTaskService(
            $pickingTaskRepository,
            $stockReservationRepository,
            $itemRepository,
            $uomConversionService,
            $stockLedgerService,
            $stockReservationService,
        );

        $result = $service->createTask([
            'tenant_id' => 1,
            'item_id' => 100,
            'source_warehouse_id' => 10,
            'transaction_uom_id' => 2,
            'reserved_quantity' => 5,
            'picked_quantity' => 2,
        ]);

        self::assertTrue(
            $result->isSuccess(),
            $result->isFailure() ? $result->errorOrFail()->message : 'Expected success.',
        );
    }

    public function testItCompletesPickingTaskByConsumingReservationAndPostingMovement(): void
    {
        $pickingTaskRepository = $this->createMock(PickingTaskRepositoryInterface::class);
        $stockReservationRepository = $this->createMock(StockReservationRepositoryInterface::class);
        $itemRepository = $this->createMock(ItemRepositoryInterface::class);
        $uomConversionService = $this->createMock(UomConversionServiceInterface::class);
        $stockLedgerService = $this->createMock(StockLedgerServiceInterface::class);
        $stockReservationService = $this->createMock(StockReservationServiceInterface::class);

        $existing = new DataRecord([
            'id' => 801,
            'tenant_id' => 1,
            'item_id' => 100,
            'source_warehouse_id' => 10,
            'source_location_id' => 11,
            'transaction_uom_id' => 2,
            'picked_quantity' => 2.0,
            'status' => 'PENDING',
            'stock_reservation_id' => 501,
            'notes' => 'pick for shipment',
            'completed_at' => '2026-05-28 15:00:00',
        ]);
        $updated = new DataRecord([
            'id' => 801,
            'tenant_id' => 1,
            'item_id' => 100,
            'source_warehouse_id' => 10,
            'source_location_id' => 11,
            'transaction_uom_id' => 2,
            'picked_quantity' => 2.0,
            'status' => 'COMPLETED',
            'stock_reservation_id' => 501,
            'notes' => 'pick for shipment',
            'completed_at' => '2026-05-28 15:00:00',
        ]);

        $pickingTaskRepository
            ->method('findById')
            ->with(801)
            ->willReturn($existing);

        $pickingTaskRepository
            ->method('transaction')
            ->willReturnCallback(static fn (callable $callback) => $callback());

        $updateCalls = [];
        $pickingTaskRepository
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

        $stockReservationService
            ->expects(self::once())
            ->method('consume')
            ->with(501, ['quantity' => 2.0])
            ->willReturn(Result::success(new DataRecord(['id' => 501])));

        $stockLedgerService
            ->expects(self::once())
            ->method('recordMovement')
            ->with(self::callback(static function (array $payload): bool {
                return ($payload['item_id'] ?? null) === 100
                    && ($payload['warehouse_id'] ?? null) === 10
                    && ($payload['location_id'] ?? null) === 11
                    && ($payload['direction'] ?? null) === 'OUT'
                    && ($payload['movement_type'] ?? null) === 'PICKING_TASK'
                    && ($payload['source_type'] ?? null) === 'picking_task'
                    && ($payload['source_id'] ?? null) === 801;
            }))
            ->willReturn(Result::success(new DataRecord(['id' => 9103])));

        $service = new PickingTaskService(
            $pickingTaskRepository,
            $stockReservationRepository,
            $itemRepository,
            $uomConversionService,
            $stockLedgerService,
            $stockReservationService,
        );

        $result = $service->updateTask(801, ['status' => 'COMPLETED']);

        self::assertTrue(
            $result->isSuccess(),
            $result->isFailure() ? $result->errorOrFail()->message : 'Expected success.',
        );
        self::assertCount(2, $updateCalls);
        self::assertSame([801, ['status' => 'COMPLETED']], $updateCalls[0]);
        self::assertSame(801, $updateCalls[1][0]);
        self::assertSame(9103, $updateCalls[1][1]['stock_movement_id'] ?? null);
        self::assertSame('COMPLETED', $updateCalls[1][1]['status'] ?? null);
        self::assertSame('2026-05-28 15:00:00', $updateCalls[1][1]['completed_at'] ?? null);
    }
}
