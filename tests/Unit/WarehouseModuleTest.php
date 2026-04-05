<?php
declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modules\Warehouse\Domain\Entities\Warehouse;
use Modules\Warehouse\Domain\Entities\WarehouseLocation;

class WarehouseModuleTest extends TestCase
{
    private function makeWarehouse(array $overrides = []): Warehouse
    {
        return new Warehouse(
            $overrides['id'] ?? 1, $overrides['tenant_id'] ?? 1,
            $overrides['name'] ?? 'Main WH', $overrides['code'] ?? 'WH-001',
            $overrides['type'] ?? 'standard', $overrides['address'] ?? '123 Main St',
            $overrides['is_active'] ?? true, null, null, null, null
        );
    }
    private function makeLocation(array $overrides = []): WarehouseLocation
    {
        return new WarehouseLocation(
            $overrides['id'] ?? 1, $overrides['tenant_id'] ?? 1,
            $overrides['warehouse_id'] ?? 1, null, 'Aisle A', 'A-001', 'aisle', 0, true, null, null
        );
    }

    public function test_warehouse_creation(): void
    {
        $wh = $this->makeWarehouse();
        $this->assertEquals(1, $wh->getId());
        $this->assertEquals('WH-001', $wh->getCode());
        $this->assertTrue($wh->isActive());
    }

    public function test_warehouse_activate_deactivate(): void
    {
        $wh = $this->makeWarehouse(['is_active' => false]);
        $this->assertFalse($wh->isActive());
        $wh->activate();
        $this->assertTrue($wh->isActive());
        $wh->deactivate();
        $this->assertFalse($wh->isActive());
    }

    public function test_warehouse_location_creation(): void
    {
        $loc = $this->makeLocation();
        $this->assertEquals(1, $loc->getWarehouseId());
        $this->assertEquals('A-001', $loc->getCode());
        $this->assertEquals('aisle', $loc->getType());
        $this->assertTrue($loc->isActive());
    }

    // ──────────────────────────────────────────────────────────────────────
    // Additional Warehouse entity tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_warehouse_types(): void
    {
        foreach (['standard', 'cold_storage', 'hazmat', 'bonded'] as $type) {
            $wh = $this->makeWarehouse(['type' => $type]);
            $this->assertEquals($type, $wh->getType());
        }
    }

    public function test_warehouse_optional_fields(): void
    {
        $wh = $this->makeWarehouse();
        $this->assertNull($wh->getManagerId());
        $this->assertNull($wh->getMetadata());
    }

    public function test_warehouse_with_manager_and_metadata(): void
    {
        $wh = new Warehouse(
            2, 1, 'Cold Storage', 'WH-002', 'cold_storage', '456 Freeze Ave',
            true, 5, ['temperature' => '-18C'], null, null,
        );
        $this->assertEquals(5, $wh->getManagerId());
        $this->assertEquals(['temperature' => '-18C'], $wh->getMetadata());
    }

    public function test_warehouse_name_and_address(): void
    {
        $wh = $this->makeWarehouse(['name' => 'East Warehouse', 'address' => '789 East Blvd']);
        $this->assertEquals('East Warehouse', $wh->getName());
        $this->assertEquals('789 East Blvd', $wh->getAddress());
    }

    // ──────────────────────────────────────────────────────────────────────
    // WarehouseLocation – hierarchy support
    // ──────────────────────────────────────────────────────────────────────

    public function test_warehouse_location_hierarchical(): void
    {
        $rack = new WarehouseLocation(2, 1, 1, 1, 'Rack A1', 'A1-R', 'rack', 1, true, null, null);
        $this->assertEquals(1, $rack->getParentId());
        $this->assertEquals(1, $rack->getLevel());
        $this->assertEquals('rack', $rack->getType());
    }

    public function test_warehouse_location_bin_level(): void
    {
        $bin = new WarehouseLocation(3, 1, 1, 2, 'Bin A1-R-01', 'A1-R-01', 'bin', 2, true, null, null);
        $this->assertEquals('bin', $bin->getType());
        $this->assertEquals(2, $bin->getLevel());
    }

    public function test_warehouse_location_all_types(): void
    {
        foreach (['aisle', 'rack', 'shelf', 'bin', 'zone'] as $type) {
            $loc = new WarehouseLocation(1, 1, 1, null, 'Loc', 'L', $type, 0, true, null, null);
            $this->assertEquals($type, $loc->getType());
        }
    }
}
