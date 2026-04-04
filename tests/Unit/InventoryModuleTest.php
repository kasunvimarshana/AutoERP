<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modules\Inventory\Domain\ValueObjects\ValuationMethod;
use Modules\Inventory\Domain\ValueObjects\ManagementMethod;
use Modules\Inventory\Domain\ValueObjects\StockRotationStrategy;
use Modules\Inventory\Domain\ValueObjects\AllocationAlgorithm;
use Modules\Inventory\Domain\ValueObjects\CycleCountMethod;
use Modules\Inventory\Domain\Entities\InventoryLevel;
use Modules\Inventory\Domain\Entities\InventoryValuationLayer;

class InventoryModuleTest extends TestCase
{
    // --------------- ValuationMethod VO ---------------

    public function test_valuation_method_fifo(): void
    {
        $vo = ValuationMethod::from(ValuationMethod::FIFO);
        $this->assertSame('fifo', (string) $vo);
    }

    public function test_valuation_method_lifo(): void
    {
        $vo = ValuationMethod::from(ValuationMethod::LIFO);
        $this->assertSame('lifo', (string) $vo);
    }

    public function test_valuation_method_average(): void
    {
        $vo = ValuationMethod::from(ValuationMethod::AVERAGE);
        $this->assertSame('average', (string) $vo);
    }

    public function test_valuation_method_specific(): void
    {
        $vo = ValuationMethod::from(ValuationMethod::SPECIFIC);
        $this->assertSame('specific', (string) $vo);
    }

    public function test_valuation_method_standard(): void
    {
        $vo = ValuationMethod::from(ValuationMethod::STANDARD);
        $this->assertSame('standard', (string) $vo);
    }

    public function test_valuation_method_invalid_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ValuationMethod::from('bogus');
    }

    public function test_valuation_method_valid_returns_array(): void
    {
        $this->assertIsArray(ValuationMethod::valid());
        $this->assertContains('fifo', ValuationMethod::valid());
    }

    // --------------- ManagementMethod VO ---------------

    public function test_management_method_standard(): void
    {
        $vo = ManagementMethod::from(ManagementMethod::STANDARD);
        $this->assertSame('standard', (string) $vo);
    }

    public function test_management_method_batch(): void
    {
        $vo = ManagementMethod::from(ManagementMethod::BATCH);
        $this->assertSame('batch', (string) $vo);
    }

    public function test_management_method_lot(): void
    {
        $vo = ManagementMethod::from(ManagementMethod::LOT);
        $this->assertSame('lot', (string) $vo);
    }

    public function test_management_method_serial(): void
    {
        $vo = ManagementMethod::from(ManagementMethod::SERIAL);
        $this->assertSame('serial', (string) $vo);
    }

    public function test_management_method_batch_and_serial(): void
    {
        $vo = ManagementMethod::from(ManagementMethod::BATCH_AND_SERIAL);
        $this->assertSame('batch_and_serial', (string) $vo);
    }

    public function test_management_method_invalid_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ManagementMethod::from('unknown');
    }

    // --------------- StockRotationStrategy VO ---------------

    public function test_stock_rotation_fifo(): void
    {
        $vo = StockRotationStrategy::from(StockRotationStrategy::FIFO);
        $this->assertSame('fifo', (string) $vo);
    }

    public function test_stock_rotation_lifo(): void
    {
        $vo = StockRotationStrategy::from(StockRotationStrategy::LIFO);
        $this->assertSame('lifo', (string) $vo);
    }

    public function test_stock_rotation_fefo(): void
    {
        $vo = StockRotationStrategy::from(StockRotationStrategy::FEFO);
        $this->assertSame('fefo', (string) $vo);
    }

    public function test_stock_rotation_lefo(): void
    {
        $vo = StockRotationStrategy::from(StockRotationStrategy::LEFO);
        $this->assertSame('lefo', (string) $vo);
    }

    public function test_stock_rotation_invalid_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        StockRotationStrategy::from('invalid');
    }

    // --------------- AllocationAlgorithm VO ---------------

    public function test_allocation_algorithm_fifo(): void
    {
        $vo = AllocationAlgorithm::from(AllocationAlgorithm::FIFO);
        $this->assertSame('fifo', (string) $vo);
    }

    public function test_allocation_algorithm_nearest(): void
    {
        $vo = AllocationAlgorithm::from(AllocationAlgorithm::NEAREST);
        $this->assertSame('nearest', (string) $vo);
    }

    public function test_allocation_algorithm_zone_based(): void
    {
        $vo = AllocationAlgorithm::from(AllocationAlgorithm::ZONE_BASED);
        $this->assertSame('zone_based', (string) $vo);
    }

    public function test_allocation_algorithm_fefo(): void
    {
        $vo = AllocationAlgorithm::from(AllocationAlgorithm::FEFO);
        $this->assertSame('fefo', (string) $vo);
    }

    public function test_allocation_algorithm_invalid_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        AllocationAlgorithm::from('wrong');
    }

    // --------------- CycleCountMethod VO ---------------

    public function test_cycle_count_full(): void
    {
        $vo = CycleCountMethod::from(CycleCountMethod::FULL);
        $this->assertSame('full', (string) $vo);
    }

    public function test_cycle_count_partial(): void
    {
        $vo = CycleCountMethod::from(CycleCountMethod::PARTIAL);
        $this->assertSame('partial', (string) $vo);
    }

    public function test_cycle_count_abc(): void
    {
        $vo = CycleCountMethod::from(CycleCountMethod::ABC);
        $this->assertSame('abc', (string) $vo);
    }

    public function test_cycle_count_random(): void
    {
        $vo = CycleCountMethod::from(CycleCountMethod::RANDOM);
        $this->assertSame('random', (string) $vo);
    }

    public function test_cycle_count_periodic(): void
    {
        $vo = CycleCountMethod::from(CycleCountMethod::PERIODIC);
        $this->assertSame('periodic', (string) $vo);
    }

    public function test_cycle_count_invalid_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CycleCountMethod::from('nope');
    }

    // --------------- InventoryLevel entity ---------------

    private function makeLevel(float $onHand = 100.0, float $reserved = 20.0, float $available = 80.0): InventoryLevel
    {
        return new InventoryLevel(
            id: 1,
            tenantId: 1,
            productId: 10,
            warehouseId: 5,
            locationId: 3,
            quantityOnHand: $onHand,
            quantityReserved: $reserved,
            quantityAvailable: $available,
            quantityOnOrder: 0.0,
        );
    }

    public function test_inventory_level_reserve_reduces_available(): void
    {
        $level = $this->makeLevel();
        $level->reserve(10.0);
        $this->assertSame(70.0, $level->quantityAvailable);
    }

    public function test_inventory_level_reserve_increases_reserved(): void
    {
        $level = $this->makeLevel();
        $level->reserve(10.0);
        $this->assertSame(30.0, $level->quantityReserved);
    }

    public function test_inventory_level_reserve_insufficient_stock_throws(): void
    {
        $level = $this->makeLevel();
        $this->expectException(\DomainException::class);
        $level->reserve(200.0);
    }

    public function test_inventory_level_release_reduces_reserved(): void
    {
        $level = $this->makeLevel();
        $level->release(10.0);
        $this->assertSame(10.0, $level->quantityReserved);
    }

    public function test_inventory_level_release_increases_available(): void
    {
        $level = $this->makeLevel();
        $level->release(10.0);
        $this->assertSame(90.0, $level->quantityAvailable);
    }

    public function test_inventory_level_release_caps_at_reserved(): void
    {
        $level = $this->makeLevel();
        $level->release(999.0);
        $this->assertSame(0.0, $level->quantityReserved);
        $this->assertSame(100.0, $level->quantityAvailable);
    }

    public function test_inventory_level_adjust_updates_on_hand(): void
    {
        $level = $this->makeLevel();
        $level->adjust(150.0);
        $this->assertSame(150.0, $level->quantityOnHand);
    }

    public function test_inventory_level_adjust_recalculates_available(): void
    {
        $level = $this->makeLevel();
        $level->adjust(50.0);
        $this->assertSame(30.0, $level->quantityAvailable);
    }

    public function test_inventory_level_id_is_stored(): void
    {
        $level = $this->makeLevel();
        $this->assertSame(1, $level->id);
    }

    public function test_inventory_level_construction_defaults(): void
    {
        $level = new InventoryLevel(
            id: null,
            tenantId: 2,
            productId: 99,
            warehouseId: 1,
            locationId: 1,
            quantityOnHand: 0.0,
            quantityReserved: 0.0,
            quantityAvailable: 0.0,
            quantityOnOrder: 0.0,
        );
        $this->assertNull($level->id);
        $this->assertSame('available', $level->stockStatus);
    }

    // --------------- InventoryValuationLayer entity ---------------

    private function makeLayer(
        float $quantity = 100.0,
        float $unitCost = 10.0,
        ?int $id = 1
    ): InventoryValuationLayer {
        return new InventoryValuationLayer(
            id: $id,
            tenantId: 1,
            productId: 10,
            warehouseId: 5,
            valuationMethod: ValuationMethod::FIFO,
            quantity: $quantity,
            remainingQuantity: $quantity,
            unitCost: $unitCost,
            totalCost: $quantity * $unitCost,
        );
    }

    public function test_valuation_layer_construction_stores_id(): void
    {
        $layer = $this->makeLayer();
        $this->assertSame(1, $layer->id);
    }

    public function test_valuation_layer_construction_stores_remaining_quantity(): void
    {
        $layer = $this->makeLayer(50.0);
        $this->assertSame(50.0, $layer->remainingQuantity);
    }

    public function test_valuation_layer_has_stock_returns_true_when_remaining(): void
    {
        $layer = $this->makeLayer(10.0);
        $this->assertTrue($layer->hasStock());
    }

    public function test_valuation_layer_has_stock_returns_false_when_empty(): void
    {
        $layer = $this->makeLayer(0.0);
        $this->assertFalse($layer->hasStock());
    }

    public function test_valuation_layer_consume_reduces_remaining_quantity(): void
    {
        $layer = $this->makeLayer(100.0, 5.0);
        $layer->consume(30.0);
        $this->assertSame(70.0, $layer->remainingQuantity);
    }

    public function test_valuation_layer_consume_returns_correct_cost(): void
    {
        $layer = $this->makeLayer(100.0, 5.0);
        $cost = $layer->consume(20.0);
        $this->assertSame(100.0, $cost); // 20 * 5
    }

    public function test_valuation_layer_consume_partial_layer(): void
    {
        $layer = $this->makeLayer(10.0, 8.0);
        $cost = $layer->consume(5.0);
        $this->assertSame(40.0, $cost);
        $this->assertSame(5.0, $layer->remainingQuantity);
    }

    public function test_valuation_layer_consume_clamps_to_available(): void
    {
        $layer = $this->makeLayer(10.0, 4.0);
        // Request more than available; should consume only what is available
        $cost = $layer->consume(999.0);
        $this->assertSame(40.0, $cost); // 10 * 4
        $this->assertSame(0.0, $layer->remainingQuantity);
    }

    public function test_valuation_layer_consume_zero_remaining_returns_zero(): void
    {
        $layer = $this->makeLayer(0.0, 10.0);
        $cost = $layer->consume(5.0);
        $this->assertSame(0.0, $cost);
    }

    public function test_valuation_layer_consume_updates_total_cost(): void
    {
        $layer = $this->makeLayer(100.0, 3.0);
        $layer->consume(40.0);
        // remaining = 60, total_cost = 60 * 3 = 180
        $this->assertSame(180.0, $layer->totalCost);
    }

    public function test_valuation_layer_sequential_consume(): void
    {
        $layer = $this->makeLayer(100.0, 2.0);
        $layer->consume(30.0);
        $layer->consume(50.0);
        $this->assertSame(20.0, $layer->remainingQuantity);
    }

    public function test_valuation_layer_optional_fields_nullable(): void
    {
        $layer = new InventoryValuationLayer(
            id: null,
            tenantId: 1,
            productId: 1,
            warehouseId: 1,
            valuationMethod: ValuationMethod::AVERAGE,
            quantity: 10.0,
            remainingQuantity: 10.0,
            unitCost: 1.0,
            totalCost: 10.0,
        );
        $this->assertNull($layer->id);
        $this->assertNull($layer->batchId);
        $this->assertNull($layer->receiptDate);
        $this->assertNull($layer->referenceId);
        $this->assertNull($layer->referenceType);
    }

    // --------------- InventoryLevel::issue() + receive() ---------------

    public function test_inventory_level_issue_reduces_on_hand(): void
    {
        $level = $this->makeLevel(100.0, 30.0, 70.0);
        $level->issue(20.0);
        $this->assertSame(80.0, $level->quantityOnHand);
    }

    public function test_inventory_level_issue_reduces_reserved(): void
    {
        $level = $this->makeLevel(100.0, 30.0, 70.0);
        $level->issue(20.0);
        $this->assertSame(10.0, $level->quantityReserved);
    }

    public function test_inventory_level_issue_does_not_change_available(): void
    {
        // quantityAvailable was already reduced when reserve() was called
        $level = $this->makeLevel(100.0, 30.0, 70.0);
        $level->issue(20.0);
        $this->assertSame(70.0, $level->quantityAvailable);
    }

    public function test_inventory_level_issue_clamps_to_reserved(): void
    {
        $level = $this->makeLevel(100.0, 10.0, 90.0);
        // Issuing exactly reserved qty
        $level->issue(10.0);
        $this->assertSame(0.0, $level->quantityReserved);
        $this->assertSame(90.0, $level->quantityOnHand);
    }

    public function test_inventory_level_issue_exceeds_reserved_throws(): void
    {
        $level = $this->makeLevel(100.0, 10.0, 90.0);
        $this->expectException(\DomainException::class);
        $level->issue(50.0);
    }

    public function test_inventory_level_issue_zero_qty_is_noop(): void
    {
        $level = $this->makeLevel(100.0, 20.0, 80.0);
        $level->issue(0.0);
        $this->assertSame(100.0, $level->quantityOnHand);
        $this->assertSame(20.0, $level->quantityReserved);
    }

    public function test_inventory_level_receive_increases_on_hand(): void
    {
        $level = $this->makeLevel(50.0, 0.0, 50.0);
        $level->receive(25.0);
        $this->assertSame(75.0, $level->quantityOnHand);
    }

    public function test_inventory_level_receive_increases_available(): void
    {
        $level = $this->makeLevel(50.0, 10.0, 40.0);
        $level->receive(25.0);
        $this->assertSame(65.0, $level->quantityAvailable);
    }

    public function test_inventory_level_receive_does_not_change_reserved(): void
    {
        $level = $this->makeLevel(50.0, 10.0, 40.0);
        $level->receive(25.0);
        $this->assertSame(10.0, $level->quantityReserved);
    }

    public function test_inventory_level_reserve_then_issue_full_cycle(): void
    {
        $level = $this->makeLevel(100.0, 0.0, 100.0);
        // Reserve
        $level->reserve(30.0);
        $this->assertSame(30.0, $level->quantityReserved);
        $this->assertSame(70.0, $level->quantityAvailable);
        $this->assertSame(100.0, $level->quantityOnHand);
        // Issue (confirm dispatch)
        $level->issue(30.0);
        $this->assertSame(0.0, $level->quantityReserved);
        $this->assertSame(70.0, $level->quantityAvailable);
        $this->assertSame(70.0, $level->quantityOnHand);
    }
}
