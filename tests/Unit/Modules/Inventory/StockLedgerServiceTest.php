<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Inventory;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Repositories\BatchRepositoryInterface;
use Modules\Inventory\Application\Repositories\SerialRepositoryInterface;
use Modules\Inventory\Application\Repositories\StockLevelRepositoryInterface;
use Modules\Inventory\Application\Repositories\StockMovementRepositoryInterface;
use Modules\Inventory\Application\Services\StockLedgerService;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\UOM\Application\Contracts\Services\UomConversionServiceInterface;
use PHPUnit\Framework\TestCase;

final class StockLedgerServiceTest extends TestCase
{
    public function testItRecordsMovementAndSynchronizesStockLevel(): void
    {
        $movementRepository = $this->createMock(StockMovementRepositoryInterface::class);
        $stockLevelRepository = $this->createMock(StockLevelRepositoryInterface::class);
        $itemRepository = $this->createMock(ItemRepositoryInterface::class);
        $uomConversionService = $this->createMock(UomConversionServiceInterface::class);
        $batchRepository = $this->createMock(BatchRepositoryInterface::class);
        $serialRepository = $this->createMock(SerialRepositoryInterface::class);

        $itemRepository
            ->method('findByIdInTenant')
            ->with(100, 1)
            ->willReturn(new DataRecord([
                'id' => 100,
                'tenant_id' => 1,
                'base_uom_id' => 1,
                'is_stockable' => true,
                'is_serial_tracked' => false,
            ]));

        $uomConversionService
            ->method('convert')
            ->with(10.0, 2, 1, 1, 100)
            ->willReturn(Result::success(20.0));

        $stockLevelRepository
            ->method('list')
            ->willReturn([
                new DataRecord([
                    'id' => 55,
                    'quantity_on_hand' => 30.0,
                    'quantity_reserved' => 4.0,
                    'row_version' => 1,
                    'unit_cost' => 5.5,
                ]),
            ]);

        $movementRepository
            ->method('transaction')
            ->willReturnCallback(static fn (callable $callback) => $callback());

        $movementRepository
            ->expects(self::once())
            ->method('create')
            ->with(self::callback(static function (array $payload): bool {
                return ($payload['transaction_uom_id'] ?? null) === 2
                    && ($payload['base_uom_id'] ?? null) === 1
                    && (float) ($payload['quantity'] ?? 0) === 10.0
                    && (float) ($payload['base_quantity'] ?? 0) === 20.0
                    && ($payload['movement_type'] ?? null) === 'PURCHASE_RECEIPT'
                    && (float) ($payload['balance_quantity'] ?? 0) === 50.0;
            }))
            ->willReturn(new DataRecord([
                'id' => 999,
                'performed_at' => '2026-05-28 10:00:00',
            ]));

        $stockLevelRepository
            ->expects(self::once())
            ->method('update')
            ->with(55, self::callback(static function (array $payload): bool {
                return ($payload['quantity_on_hand'] ?? null) === 50.0
                    && ($payload['quantity_reserved'] ?? null) === 4.0
                    && ($payload['last_movement_at'] ?? null) === '2026-05-28 10:00:00';
            }))
            ->willReturn(new DataRecord(['id' => 55]));

        $service = new StockLedgerService(
            $movementRepository,
            $stockLevelRepository,
            $itemRepository,
            $uomConversionService,
            $batchRepository,
            $serialRepository,
        );

        $result = $service->recordMovement([
            'tenant_id' => 1,
            'item_id' => 100,
            'warehouse_id' => 10,
            'uom_id' => 2,
            'quantity' => 10,
            'direction' => 'IN',
            'movement_type' => 'PURCHASE_RECEIPT',
        ]);

        self::assertTrue(
            $result->isSuccess(),
            $result->isFailure() ? $result->errorOrFail()->message : 'Expected success.',
        );
    }
}
