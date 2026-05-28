<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Inventory;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Inventory\Application\Repositories\StockLevelRepositoryInterface;
use Modules\Inventory\Application\Services\StockLevelService;
use PHPUnit\Framework\TestCase;

final class StockLevelServiceTest extends TestCase
{
    public function testItCreatesStockLevelWithDefaultSnapshotQuantities(): void
    {
        $repository = $this->createMock(StockLevelRepositoryInterface::class);

        $repository
            ->expects(self::once())
            ->method('create')
            ->with(self::callback(static function (array $payload): bool {
                return ($payload['tenant_id'] ?? null) === 1
                    && ($payload['item_id'] ?? null) === 100
                    && ($payload['warehouse_id'] ?? null) === 20
                    && ($payload['base_uom_id'] ?? null) === 1
                    && ($payload['row_version'] ?? null) === 1
                    && (float) ($payload['quantity_on_hand'] ?? 0) === 0.0
                    && (float) ($payload['quantity_reserved'] ?? 0) === 0.0
                    && (float) ($payload['quantity_blocked'] ?? 0) === 0.0
                    && (float) ($payload['quantity_damaged'] ?? 0) === 0.0
                    && (float) ($payload['quantity_in_transit'] ?? 0) === 0.0
                    && ($payload['condition'] ?? null) === 'good';
            }))
            ->willReturn(new DataRecord(['id' => 40]));

        $service = new StockLevelService($repository);

        $result = $service->createLevel([
            'tenant_id' => 1,
            'item_id' => 100,
            'warehouse_id' => 20,
            'base_uom_id' => 1,
        ]);

        self::assertTrue($result->isSuccess());
    }

    public function testItRejectsStockLevelDimensionMutationOnUpdate(): void
    {
        $repository = $this->createMock(StockLevelRepositoryInterface::class);
        $repository
            ->method('findById')
            ->with(40)
            ->willReturn(new DataRecord([
                'id' => 40,
                'tenant_id' => 1,
                'item_id' => 100,
                'warehouse_id' => 20,
                'base_uom_id' => 1,
                'quantity_on_hand' => 0.0,
                'quantity_reserved' => 0.0,
                'quantity_blocked' => 0.0,
                'quantity_damaged' => 0.0,
                'quantity_in_transit' => 0.0,
                'condition' => 'good',
            ]));

        $repository->expects(self::never())->method('update');

        $service = new StockLevelService($repository);

        $result = $service->updateLevel(40, ['warehouse_id' => 30]);

        self::assertTrue($result->isFailure());
    }
}
