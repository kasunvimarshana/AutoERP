<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Inventory;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Inventory\Application\Repositories\InventoryCostLayerRepositoryInterface;
use Modules\Inventory\Application\Services\InventoryCostLayerService;
use PHPUnit\Framework\TestCase;

final class InventoryCostLayerServiceTest extends TestCase
{
    public function testItCreatesCostLayerWithDefaultRowVersionAndClosedFlag(): void
    {
        $repository = $this->createMock(InventoryCostLayerRepositoryInterface::class);

        $repository
            ->expects(self::once())
            ->method('create')
            ->with(self::callback(static function (array $payload): bool {
                return ($payload['tenant_id'] ?? null) === 1
                    && ($payload['item_id'] ?? null) === 100
                    && ($payload['layer_date'] ?? null) === '2026-05-28'
                    && (float) ($payload['quantity_in'] ?? 0) === 5.0
                    && (float) ($payload['quantity_remaining'] ?? 0) === 5.0
                    && (float) ($payload['unit_cost'] ?? 0) === 10.0
                    && ($payload['row_version'] ?? null) === 1
                    && ($payload['is_closed'] ?? null) === false;
            }))
            ->willReturn(new DataRecord(['id' => 30]));

        $service = new InventoryCostLayerService($repository);

        $result = $service->createLayer([
            'tenant_id' => 1,
            'item_id' => 100,
            'layer_date' => '2026-05-28',
            'quantity_in' => 5,
            'quantity_remaining' => 5,
            'unit_cost' => 10,
        ]);

        self::assertTrue($result->isSuccess());
    }

    public function testItRejectsClosedCostLayerStructuralMutations(): void
    {
        $repository = $this->createMock(InventoryCostLayerRepositoryInterface::class);
        $repository
            ->method('findById')
            ->with(30)
            ->willReturn(new DataRecord([
                'id' => 30,
                'tenant_id' => 1,
                'item_id' => 100,
                'layer_date' => '2026-05-28',
                'quantity_in' => 5.0,
                'quantity_remaining' => 1.0,
                'unit_cost' => 10.0,
                'is_closed' => true,
            ]));

        $repository->expects(self::never())->method('update');

        $service = new InventoryCostLayerService($repository);

        $result = $service->updateLayer(30, ['item_id' => 200]);

        self::assertTrue($result->isFailure());
    }
}
