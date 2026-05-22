<?php

declare(strict_types=1);

namespace Tests\Unit\Inventory;

use Illuminate\Support\Collection;
use Modules\Inventory\Application\Support\AllocationHelper;
use Tests\TestCase;

class AllocationHelperTest extends TestCase
{
    public function testGreedyAllocateConsumesCandidatesInOrderUntilRequiredQuantityIsMet(): void
    {
        $candidates = new Collection([
            (object) [
                'stock_level_id' => 10,
                'location_id' => 100,
                'batch_id' => 1,
                'serial_id' => null,
                'available_quantity' => 4,
                'unit_cost' => 50,
                'batch_number' => 'B-001',
                'lot_number' => 'L-001',
            ],
            (object) [
                'stock_level_id' => 11,
                'location_id' => 100,
                'batch_id' => 2,
                'serial_id' => null,
                'available_quantity' => 8,
                'unit_cost' => 51,
                'batch_number' => 'B-002',
                'lot_number' => 'L-002',
            ],
        ]);

        $lines = AllocationHelper::greedyAllocate($candidates, 9);

        $this->assertCount(2, $lines);
        $this->assertSame(4.0, $lines[0]->quantity);
        $this->assertSame(5.0, $lines[1]->quantity);
        $this->assertSame(10, $lines[0]->stockLevelId);
        $this->assertSame(11, $lines[1]->stockLevelId);
    }

    public function testGreedyAllocateSkipsZeroOrNegativeAvailableRows(): void
    {
        $candidates = new Collection([
            (object) [
                'stock_level_id' => 1,
                'location_id' => 1,
                'available_quantity' => 0,
            ],
            (object) [
                'stock_level_id' => 2,
                'location_id' => 1,
                'available_quantity' => -3,
            ],
            (object) [
                'stock_level_id' => 3,
                'location_id' => 1,
                'available_quantity' => 2,
            ],
        ]);

        $lines = AllocationHelper::greedyAllocate($candidates, 1);

        $this->assertCount(1, $lines);
        $this->assertSame(3, $lines[0]->stockLevelId);
        $this->assertSame(1.0, $lines[0]->quantity);
    }
}
