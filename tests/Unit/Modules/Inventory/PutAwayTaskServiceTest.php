<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Inventory;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\StockLedgerServiceInterface;
use Modules\Inventory\Application\Repositories\PutAwayTaskRepositoryInterface;
use Modules\Inventory\Application\Services\PutAwayTaskService;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\UOM\Application\Contracts\Services\UomConversionServiceInterface;
use PHPUnit\Framework\TestCase;

final class PutAwayTaskServiceTest extends TestCase
{
    public function testItCreatesValidatedPutAwayTaskWithBaseQuantity(): void
    {
        $putAwayTaskRepository = $this->createMock(PutAwayTaskRepositoryInterface::class);
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
            ->willReturn(Result::success(8.0));

        $putAwayTaskRepository
            ->method('transaction')
            ->willReturnCallback(static fn (callable $callback) => $callback());

        $putAwayTaskRepository
            ->expects(self::once())
            ->method('create')
            ->with(self::callback(static function (array $payload): bool {
                return ($payload['tenant_id'] ?? null) === 1
                    && ($payload['item_id'] ?? null) === 100
                    && ($payload['target_warehouse_id'] ?? null) === 20
                    && ($payload['transaction_uom_id'] ?? null) === 2
                    && ($payload['base_uom_id'] ?? null) === 1
                    && (float) ($payload['quantity'] ?? 0) === 4.0
                    && (float) ($payload['base_quantity'] ?? 0) === 8.0
                        && array_key_exists('stock_movement_id', $payload)
                        && $payload['stock_movement_id'] === null;
            }))
            ->willReturn(new DataRecord(['id' => 900]));

        $service = new PutAwayTaskService(
            $putAwayTaskRepository,
            $itemRepository,
            $uomConversionService,
            $stockLedgerService,
        );

        $result = $service->createTask([
            'tenant_id' => 1,
            'item_id' => 100,
            'target_warehouse_id' => 20,
            'transaction_uom_id' => 2,
            'quantity' => 4,
        ]);

        self::assertTrue(
            $result->isSuccess(),
            $result->isFailure() ? $result->errorOrFail()->message : 'Expected success.',
        );
    }

    public function testItCompletesPutAwayTaskByPostingInboundMovement(): void
    {
        $putAwayTaskRepository = $this->createMock(PutAwayTaskRepositoryInterface::class);
        $itemRepository = $this->createMock(ItemRepositoryInterface::class);
        $uomConversionService = $this->createMock(UomConversionServiceInterface::class);
        $stockLedgerService = $this->createMock(StockLedgerServiceInterface::class);

        $existing = new DataRecord([
            'id' => 900,
            'tenant_id' => 1,
            'item_id' => 100,
            'target_warehouse_id' => 20,
            'target_location_id' => 21,
            'transaction_uom_id' => 2,
            'quantity' => 4.0,
            'status' => 'PENDING',
            'notes' => 'put away receipt',
            'completed_at' => '2026-05-28 16:00:00',
        ]);
        $updated = new DataRecord([
            'id' => 900,
            'tenant_id' => 1,
            'item_id' => 100,
            'target_warehouse_id' => 20,
            'target_location_id' => 21,
            'transaction_uom_id' => 2,
            'quantity' => 4.0,
            'status' => 'COMPLETED',
            'notes' => 'put away receipt',
            'completed_at' => '2026-05-28 16:00:00',
        ]);

        $putAwayTaskRepository
            ->method('findById')
            ->with(900)
            ->willReturn($existing);

        $putAwayTaskRepository
            ->method('transaction')
            ->willReturnCallback(static fn (callable $callback) => $callback());

        $updateCalls = [];
        $putAwayTaskRepository
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

        $stockLedgerService
            ->expects(self::once())
            ->method('recordMovement')
            ->with(self::callback(static function (array $payload): bool {
                return ($payload['item_id'] ?? null) === 100
                    && ($payload['warehouse_id'] ?? null) === 20
                    && ($payload['location_id'] ?? null) === 21
                    && ($payload['direction'] ?? null) === 'IN'
                    && ($payload['movement_type'] ?? null) === 'PUT_AWAY_TASK'
                    && ($payload['source_type'] ?? null) === 'put_away_task'
                    && ($payload['source_id'] ?? null) === 900;
            }))
            ->willReturn(Result::success(new DataRecord(['id' => 9200])));

        $service = new PutAwayTaskService(
            $putAwayTaskRepository,
            $itemRepository,
            $uomConversionService,
            $stockLedgerService,
        );

        $result = $service->updateTask(900, ['status' => 'COMPLETED']);

        self::assertTrue(
            $result->isSuccess(),
            $result->isFailure() ? $result->errorOrFail()->message : 'Expected success.',
        );
        self::assertCount(2, $updateCalls);
        self::assertSame([900, ['status' => 'COMPLETED']], $updateCalls[0]);
        self::assertSame(900, $updateCalls[1][0]);
        self::assertSame(9200, $updateCalls[1][1]['stock_movement_id'] ?? null);
        self::assertSame('COMPLETED', $updateCalls[1][1]['status'] ?? null);
        self::assertSame('2026-05-28 16:00:00', $updateCalls[1][1]['completed_at'] ?? null);
    }
}
