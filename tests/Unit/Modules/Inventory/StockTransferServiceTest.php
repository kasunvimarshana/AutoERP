<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Inventory;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\StockLedgerServiceInterface;
use Modules\Inventory\Application\Repositories\StockTransferLineRepositoryInterface;
use Modules\Inventory\Application\Repositories\StockTransferRepositoryInterface;
use Modules\Inventory\Application\Services\StockTransferService;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\UOM\Application\Contracts\Services\UomConversionServiceInterface;
use PHPUnit\Framework\TestCase;

final class StockTransferServiceTest extends TestCase
{
    public function testItCreatesValidatedTransferWithNormalizedLines(): void
    {
        $transferRepository = $this->createMock(StockTransferRepositoryInterface::class);
        $transferLineRepository = $this->createMock(StockTransferLineRepositoryInterface::class);
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
            ->willReturn(Result::success(12.0));

        $transferRepository
            ->method('transaction')
            ->willReturnCallback(static fn (callable $callback) => $callback());

        $transferRepository
            ->expects(self::once())
            ->method('create')
            ->with(self::callback(static function (array $payload): bool {
                return ($payload['tenant_id'] ?? null) === 1
                    && ($payload['from_warehouse_id'] ?? null) === 10
                    && ($payload['to_warehouse_id'] ?? null) === 20
                    && ($payload['status'] ?? null) === 'DRAFT';
            }))
            ->willReturn(new DataRecord(['id' => 700]));

        $transferLineRepository
            ->expects(self::once())
            ->method('create')
            ->with(self::callback(static function (array $payload): bool {
                return ($payload['stock_transfer_id'] ?? null) === 700
                    && ($payload['item_id'] ?? null) === 100
                    && ($payload['from_location_id'] ?? null) === 11
                    && ($payload['to_location_id'] ?? null) === 21
                    && (float) ($payload['quantity'] ?? 0) === 6.0
                    && (float) ($payload['base_quantity'] ?? 0) === 12.0
                        && array_key_exists('outgoing_movement_id', $payload)
                        && $payload['outgoing_movement_id'] === null
                        && array_key_exists('incoming_movement_id', $payload)
                        && $payload['incoming_movement_id'] === null;
            }))
            ->willReturn(new DataRecord(['id' => 701]));

        $service = new StockTransferService(
            $transferRepository,
            $transferLineRepository,
            $itemRepository,
            $uomConversionService,
            $stockLedgerService,
        );

        $result = $service->createTransfer([
            'tenant_id' => 1,
            'from_warehouse_id' => 10,
            'to_warehouse_id' => 20,
            'reference_number' => 'TR-0001',
            'requested_by' => 7,
            'from_location_id' => 11,
            'to_location_id' => 21,
            'lines' => [[
                'item_id' => 100,
                'uom_id' => 2,
                'quantity' => 6,
            ]],
        ]);

        self::assertTrue(
            $result->isSuccess(),
            $result->isFailure() ? $result->errorOrFail()->message : 'Expected success.',
        );
    }

    public function testItPostsCompletedTransferThroughLedgerOnUpdate(): void
    {
        $transferRepository = $this->createMock(StockTransferRepositoryInterface::class);
        $transferLineRepository = $this->createMock(StockTransferLineRepositoryInterface::class);
        $itemRepository = $this->createMock(ItemRepositoryInterface::class);
        $uomConversionService = $this->createMock(UomConversionServiceInterface::class);
        $stockLedgerService = $this->createMock(StockLedgerServiceInterface::class);

        $existing = new DataRecord([
            'id' => 700,
            'tenant_id' => 1,
            'from_warehouse_id' => 10,
            'to_warehouse_id' => 20,
            'from_location_id' => 11,
            'to_location_id' => 21,
            'status' => 'DRAFT',
            'approved_by' => 8,
            'transferred_at' => '2026-05-28 14:00:00',
            'notes' => 'warehouse rebalance',
        ]);
        $updated = new DataRecord([
            'id' => 700,
            'tenant_id' => 1,
            'from_warehouse_id' => 10,
            'to_warehouse_id' => 20,
            'from_location_id' => 11,
            'to_location_id' => 21,
            'status' => 'COMPLETED',
            'approved_by' => 8,
            'transferred_at' => '2026-05-28 14:00:00',
            'notes' => 'warehouse rebalance',
        ]);
        $line = new DataRecord([
            'id' => 701,
            'stock_transfer_id' => 700,
            'item_id' => 100,
            'uom_id' => 2,
            'quantity' => 6.0,
            'from_location_id' => 11,
            'to_location_id' => 21,
            'outgoing_movement_id' => null,
            'incoming_movement_id' => null,
            'unit_cost' => 4.5,
        ]);

        $transferRepository
            ->method('findById')
            ->with(700)
            ->willReturn($existing);

        $transferRepository
            ->method('transaction')
            ->willReturnCallback(static fn (callable $callback) => $callback());

        $updateCalls = [];
        $transferRepository
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

        $transferLineRepository
            ->expects(self::exactly(2))
            ->method('list')
            ->with(['stock_transfer_id' => 700])
            ->willReturn([$line]);

        $movementCalls = [];
        $stockLedgerService
            ->expects(self::exactly(2))
            ->method('recordMovement')
            ->willReturnCallback(static function (array $payload) use (&$movementCalls): Result {
                $movementCalls[] = $payload;

                return Result::success(new DataRecord([
                    'id' => count($movementCalls) === 1 ? 9101 : 9102,
                ]));
            });

        $transferLineRepository
            ->expects(self::once())
            ->method('update')
            ->with(701, ['outgoing_movement_id' => 9101, 'incoming_movement_id' => 9102])
            ->willReturn(new DataRecord(['id' => 701]));

        $service = new StockTransferService(
            $transferRepository,
            $transferLineRepository,
            $itemRepository,
            $uomConversionService,
            $stockLedgerService,
        );

        $result = $service->updateTransfer(700, ['status' => 'COMPLETED']);

        self::assertTrue(
            $result->isSuccess(),
            $result->isFailure() ? $result->errorOrFail()->message : 'Expected success.',
        );
        self::assertCount(2, $updateCalls);
        self::assertSame([700, ['status' => 'COMPLETED']], $updateCalls[0]);
        self::assertSame('COMPLETED', $updateCalls[1][1]['status'] ?? null);
        self::assertSame('2026-05-28 14:00:00', $updateCalls[1][1]['transferred_at'] ?? null);

        self::assertCount(2, $movementCalls);
        self::assertSame('OUT', $movementCalls[0]['direction'] ?? null);
        self::assertSame(10, $movementCalls[0]['warehouse_id'] ?? null);
        self::assertSame(11, $movementCalls[0]['location_id'] ?? null);
        self::assertSame('STOCK_TRANSFER_OUT', $movementCalls[0]['movement_type'] ?? null);
        self::assertSame('IN', $movementCalls[1]['direction'] ?? null);
        self::assertSame(20, $movementCalls[1]['warehouse_id'] ?? null);
        self::assertSame(21, $movementCalls[1]['location_id'] ?? null);
        self::assertSame('STOCK_TRANSFER_IN', $movementCalls[1]['movement_type'] ?? null);
    }
}
