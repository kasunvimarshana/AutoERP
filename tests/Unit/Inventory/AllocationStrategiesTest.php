<?php

declare(strict_types=1);

namespace Tests\Unit\Inventory;

use Illuminate\Support\Collection;
use Modules\Inventory\Application\DTOs\AllocationRequest;
use Modules\Inventory\Application\Strategies\Allocation\BatchAllocationStrategy;
use Modules\Inventory\Application\Strategies\Allocation\LotAllocationStrategy;
use Modules\Inventory\Application\Strategies\Allocation\QuantityAllocationStrategy;
use Tests\TestCase;

class AllocationStrategiesTest extends TestCase
{
    public function testQuantityStrategyAllocatesSequentiallyAndReturnsExpectedMethod(): void
    {
        $strategy = new QuantityAllocationStrategy();
        $request = new AllocationRequest(tenantId: 1, itemId: 1, requiredQuantity: 6);

        $candidates = new Collection([
            (object) ['stock_level_id' => 100, 'location_id' => 1, 'available_quantity' => 2],
            (object) ['stock_level_id' => 101, 'location_id' => 1, 'available_quantity' => 7],
        ]);

        $result = $strategy->allocate($candidates, $request);

        $this->assertSame('QUANTITY', $result->allocationMethod);
        $this->assertSame(6.0, $result->allocatedQuantity);
        $this->assertCount(2, $result->lines);
        $this->assertSame(2.0, $result->lines[0]->quantity);
        $this->assertSame(4.0, $result->lines[1]->quantity);
    }

    public function testBatchStrategyPrioritizesPreferredBatchIds(): void
    {
        $strategy = new BatchAllocationStrategy();
        $request = new AllocationRequest(
            tenantId: 1,
            itemId: 1,
            requiredQuantity: 3,
            preferredBatchIds: [2002, 2001]
        );

        $candidates = new Collection([
            (object) [
                'stock_level_id' => 201,
                'location_id' => 1,
                'batch_id' => 2001,
                'available_quantity' => 3,
            ],
            (object) [
                'stock_level_id' => 202,
                'location_id' => 1,
                'batch_id' => 2002,
                'available_quantity' => 3,
            ],
        ]);

        $result = $strategy->allocate($candidates, $request);

        $this->assertSame('BATCH', $result->allocationMethod);
        $this->assertCount(1, $result->lines);
        $this->assertSame(202, $result->lines[0]->stockLevelId);
    }

    public function testLotStrategyPrioritizesPreferredLotNumbersCaseInsensitive(): void
    {
        $strategy = new LotAllocationStrategy();
        $request = new AllocationRequest(
            tenantId: 1,
            itemId: 1,
            requiredQuantity: 2,
            preferredLotNumbers: ['lot-b']
        );

        $candidates = new Collection([
            (object) [
                'stock_level_id' => 301,
                'location_id' => 1,
                'lot_number' => 'LOT-A',
                'available_quantity' => 2,
            ],
            (object) [
                'stock_level_id' => 302,
                'location_id' => 1,
                'lot_number' => 'LOT-B',
                'available_quantity' => 2,
            ],
        ]);

        $result = $strategy->allocate($candidates, $request);

        $this->assertSame('LOT', $result->allocationMethod);
        $this->assertCount(1, $result->lines);
        $this->assertSame(302, $result->lines[0]->stockLevelId);
    }
}
