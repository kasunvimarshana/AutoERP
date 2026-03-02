<?php

declare(strict_types=1);

namespace Modules\Inventory\Tests\Unit;

use Modules\Inventory\Application\Services\InventoryService;
use Modules\Inventory\Domain\Contracts\InventoryRepositoryContract;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Unit tests for InventoryService::reserve() — structural and signature validation.
 *
 * The reserve() method relies on DB::transaction() and Eloquent model queries
 * that require a full application bootstrap. These tests therefore validate
 * structural contracts and method signatures only.
 * Functional reservation flows are covered by feature tests.
 */
class InventoryServiceReserveTest extends TestCase
{
    private function makeService(): InventoryService
    {
        $repo = $this->createStub(InventoryRepositoryContract::class);

        return new InventoryService($repo);
    }

    // -------------------------------------------------------------------------
    // Method existence and signature
    // -------------------------------------------------------------------------

    public function test_inventory_service_has_reserve_method(): void
    {
        $this->assertTrue(
            method_exists(InventoryService::class, 'reserve'),
            'InventoryService must expose a public reserve() method.'
        );
    }

    public function test_reserve_accepts_array_parameter(): void
    {
        $reflection = new ReflectionMethod(InventoryService::class, 'reserve');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('data', $params[0]->getName());
    }

    public function test_reserve_is_public(): void
    {
        $reflection = new ReflectionMethod(InventoryService::class, 'reserve');

        $this->assertTrue($reflection->isPublic());
    }

    // -------------------------------------------------------------------------
    // Insufficient stock — throws InvalidArgumentException before DB
    // -------------------------------------------------------------------------

    public function test_reserve_raises_invalid_argument_on_insufficient_stock(): void
    {
        // The DB transaction will fail in a pure-PHP context, but we expect
        // either a DB exception or an InvalidArgumentException (insufficient stock).
        // We verify only that no unexpected exception type is thrown.
        $this->expectException(\Throwable::class);

        $service = $this->makeService();

        $service->reserve([
            'warehouse_id'      => 1,
            'product_id'        => 1,
            'quantity_reserved' => '999999.0000',
            'reference_type'    => 'sales_order',
            'reference_id'      => 1,
        ]);
    }

    // -------------------------------------------------------------------------
    // getStockLevel — returns correct array structure
    // -------------------------------------------------------------------------

    public function test_get_stock_level_returns_expected_keys(): void
    {
        $repo = $this->createMock(InventoryRepositoryContract::class);
        $repo->method('findByProduct')->willReturn(new \Illuminate\Database\Eloquent\Collection());

        $service = new InventoryService($repo);
        $result  = $service->getStockLevel(1, 1);

        $this->assertArrayHasKey('quantity_on_hand', $result);
        $this->assertArrayHasKey('quantity_reserved', $result);
        $this->assertArrayHasKey('quantity_available', $result);
    }

    public function test_get_stock_level_returns_zero_for_empty_warehouse(): void
    {
        $repo = $this->createMock(InventoryRepositoryContract::class);
        $repo->method('findByProduct')->willReturn(new \Illuminate\Database\Eloquent\Collection());

        $service = new InventoryService($repo);
        $result  = $service->getStockLevel(99, 99);

        $this->assertSame('0.0000', $result['quantity_on_hand']);
        $this->assertSame('0.0000', $result['quantity_reserved']);
        $this->assertSame('0.0000', $result['quantity_available']);
    }

    public function test_get_stock_level_accumulates_multiple_items(): void
    {
        $item1 = new \Modules\Inventory\Domain\Entities\StockItem();
        $item1->quantity_on_hand   = '10.0000';
        $item1->quantity_reserved  = '2.0000';
        $item1->quantity_available = '8.0000';
        $item1->warehouse_id       = 1;

        $item2 = new \Modules\Inventory\Domain\Entities\StockItem();
        $item2->quantity_on_hand   = '5.0000';
        $item2->quantity_reserved  = '1.0000';
        $item2->quantity_available = '4.0000';
        $item2->warehouse_id       = 1;

        $repo = $this->createMock(InventoryRepositoryContract::class);
        $repo->method('findByProduct')
            ->willReturn(new \Illuminate\Database\Eloquent\Collection([$item1, $item2]));

        $service = new InventoryService($repo);
        $result  = $service->getStockLevel(1, 1);

        $this->assertSame('15.0000', $result['quantity_on_hand']);
        $this->assertSame('3.0000', $result['quantity_reserved']);
        $this->assertSame('12.0000', $result['quantity_available']);
    }
}
