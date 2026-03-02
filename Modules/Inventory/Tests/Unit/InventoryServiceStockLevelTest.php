<?php

declare(strict_types=1);

namespace Modules\Inventory\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Modules\Inventory\Application\Services\InventoryService;
use Modules\Inventory\Domain\Contracts\InventoryRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InventoryService::getStockLevel().
 *
 * Validates the BCMath aggregation logic that sums on_hand,
 * reserved, and available quantities across multiple stock items.
 * No database or Laravel bootstrap required — the repository is stubbed
 * and items are plain objects compatible with Collection::where().
 */
class InventoryServiceStockLevelTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helper: build a plain object representing a stock item row
    // -------------------------------------------------------------------------

    private function makeItem(
        int $warehouseId,
        string $onHand,
        string $reserved,
        string $available
    ): object {
        $item                     = new \stdClass();
        $item->warehouse_id       = $warehouseId;
        $item->quantity_on_hand   = $onHand;
        $item->quantity_reserved  = $reserved;
        $item->quantity_available = $available;

        return $item;
    }

    private function makeService(InventoryRepositoryContract $repo): InventoryService
    {
        return new InventoryService($repo);
    }

    // -------------------------------------------------------------------------
    // Empty stock
    // -------------------------------------------------------------------------

    public function test_get_stock_level_returns_zeros_when_no_items_exist(): void
    {
        $repo = $this->createMock(InventoryRepositoryContract::class);
        $repo->method('findByProduct')->willReturn(new Collection());

        $result = $this->makeService($repo)->getStockLevel(1, 1);

        $this->assertSame('0.0000', $result['quantity_on_hand']);
        $this->assertSame('0.0000', $result['quantity_reserved']);
        $this->assertSame('0.0000', $result['quantity_available']);
    }

    // -------------------------------------------------------------------------
    // Single warehouse, single item
    // -------------------------------------------------------------------------

    public function test_get_stock_level_single_item(): void
    {
        $item = $this->makeItem(5, '10.0000', '2.0000', '8.0000');

        $repo = $this->createMock(InventoryRepositoryContract::class);
        $repo->method('findByProduct')
            ->willReturn(new Collection([$item]));

        $result = $this->makeService($repo)->getStockLevel(1, 5);

        $this->assertSame('10.0000', $result['quantity_on_hand']);
        $this->assertSame('2.0000',  $result['quantity_reserved']);
        $this->assertSame('8.0000',  $result['quantity_available']);
    }

    // -------------------------------------------------------------------------
    // Multiple items in the same warehouse — aggregation
    // -------------------------------------------------------------------------

    public function test_get_stock_level_aggregates_multiple_items_in_same_warehouse(): void
    {
        // Two stock items in warehouse 3 (different batches)
        $item1 = $this->makeItem(3, '50.0000', '5.0000', '45.0000');
        $item2 = $this->makeItem(3, '30.0000', '10.0000', '20.0000');

        $repo = $this->createMock(InventoryRepositoryContract::class);
        $repo->method('findByProduct')
            ->willReturn(new Collection([$item1, $item2]));

        $result = $this->makeService($repo)->getStockLevel(1, 3);

        $this->assertSame('80.0000', $result['quantity_on_hand']);
        $this->assertSame('15.0000', $result['quantity_reserved']);
        $this->assertSame('65.0000', $result['quantity_available']);
    }

    // -------------------------------------------------------------------------
    // Warehouse filter — only items matching the warehouseId are aggregated
    // -------------------------------------------------------------------------

    public function test_get_stock_level_filters_by_warehouse_id(): void
    {
        // Items in two different warehouses — only warehouse 2 should be summed
        $itemWh2 = $this->makeItem(2, '100.0000', '0.0000', '100.0000');
        $itemWh9 = $this->makeItem(9, '999.0000', '0.0000', '999.0000');

        $repo = $this->createMock(InventoryRepositoryContract::class);
        $repo->method('findByProduct')
            ->willReturn(new Collection([$itemWh2, $itemWh9]));

        $result = $this->makeService($repo)->getStockLevel(1, 2);

        $this->assertSame('100.0000', $result['quantity_on_hand']);
        $this->assertSame('0.0000',   $result['quantity_reserved']);
        $this->assertSame('100.0000', $result['quantity_available']);
    }

    // -------------------------------------------------------------------------
    // Decimal precision — BCMath-level accuracy across many small items
    // -------------------------------------------------------------------------

    public function test_get_stock_level_uses_bcmath_precision(): void
    {
        // Classic float-drift scenario: 0.1 + 0.2 must equal exactly 0.3000
        $item1 = $this->makeItem(1, '0.1000', '0.0000', '0.1000');
        $item2 = $this->makeItem(1, '0.2000', '0.0000', '0.2000');

        $repo = $this->createMock(InventoryRepositoryContract::class);
        $repo->method('findByProduct')
            ->willReturn(new Collection([$item1, $item2]));

        $result = $this->makeService($repo)->getStockLevel(1, 1);

        // BCMath must give exactly 0.3000 — no IEEE-754 drift
        $this->assertSame('0.3000', $result['quantity_on_hand']);
    }

    // -------------------------------------------------------------------------
    // Return type verification
    // -------------------------------------------------------------------------

    public function test_get_stock_level_returns_array_with_required_keys(): void
    {
        $repo = $this->createMock(InventoryRepositoryContract::class);
        $repo->method('findByProduct')->willReturn(new Collection());

        $result = $this->makeService($repo)->getStockLevel(1, 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('quantity_on_hand', $result);
        $this->assertArrayHasKey('quantity_reserved', $result);
        $this->assertArrayHasKey('quantity_available', $result);
    }
}
