<?php
declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modules\Inventory\Domain\Entities\InventoryBatch;
use Modules\Inventory\Domain\Entities\InventoryLevel;
use Modules\Inventory\Domain\Entities\InventoryValuationLayer;

class InventoryModuleTest extends TestCase
{
    private function makeLevel(float $onHand = 100.0, float $reserved = 0.0): InventoryLevel
    {
        return new InventoryLevel(1, 1, 1, 1, null, $onHand, $reserved, 0.0, 'fifo', null, null);
    }
    private function makeValuationLayer(float $qty = 50.0, float $cost = 10.0): InventoryValuationLayer
    {
        return new InventoryValuationLayer(1, 1, 1, 1, $qty, $qty, $cost, new \DateTimeImmutable(), null, null, null, null);
    }

    public function test_inventory_level_creation(): void
    {
        $level = $this->makeLevel(100.0, 20.0);
        $this->assertEquals(100.0, $level->getQuantityOnHand());
        $this->assertEquals(20.0, $level->getQuantityReserved());
        $this->assertEquals(80.0, $level->getAvailableQuantity());
        $this->assertEquals('fifo', $level->getValuationMethod());
    }

    public function test_receive_increases_on_hand(): void
    {
        $level = $this->makeLevel(100.0);
        $level->receive(50.0);
        $this->assertEquals(150.0, $level->getQuantityOnHand());
    }

    public function test_receive_rejects_negative_quantity(): void
    {
        $level = $this->makeLevel(100.0);
        $this->expectException(\InvalidArgumentException::class);
        $level->receive(-1.0);
    }

    public function test_issue_decreases_on_hand(): void
    {
        $level = $this->makeLevel(100.0, 0.0);
        $level->issue(30.0);
        $this->assertEquals(70.0, $level->getQuantityOnHand());
    }

    public function test_issue_fails_when_insufficient(): void
    {
        $level = $this->makeLevel(50.0, 40.0);
        $this->expectException(\DomainException::class);
        $level->issue(20.0); // available = 10, requested = 20
    }

    public function test_reserve_increases_reserved(): void
    {
        $level = $this->makeLevel(100.0, 0.0);
        $level->reserve(30.0);
        $this->assertEquals(30.0, $level->getQuantityReserved());
        $this->assertEquals(70.0, $level->getAvailableQuantity());
    }

    public function test_reserve_fails_when_insufficient(): void
    {
        $level = $this->makeLevel(50.0, 40.0);
        $this->expectException(\DomainException::class);
        $level->reserve(20.0); // available = 10, requested = 20
    }

    public function test_valuation_layer_remaining_quantity(): void
    {
        $layer = $this->makeValuationLayer(50.0, 10.0);
        $this->assertEquals(50.0, $layer->getQuantityRemaining());
        $this->assertTrue($layer->hasStock());
    }

    public function test_valuation_layer_consume(): void
    {
        $layer = $this->makeValuationLayer(50.0, 10.0);
        $layer->consume(20.0);
        $this->assertEquals(30.0, $layer->getQuantityRemaining());
        $this->assertTrue($layer->hasStock());
    }

    public function test_valuation_layer_consume_all(): void
    {
        $layer = $this->makeValuationLayer(50.0, 10.0);
        $layer->consume(50.0);
        $this->assertEquals(0.0, $layer->getQuantityRemaining());
        $this->assertFalse($layer->hasStock());
    }

    public function test_float_tolerance(): void
    {
        $this->assertEquals(0.0001, InventoryLevel::FLOAT_TOLERANCE);
    }

    // ──────────────────────────────────────────────────────────────────────
    // InventoryLevel – release reservation
    // ──────────────────────────────────────────────────────────────────────

    public function test_release_reservation_decreases_reserved(): void
    {
        $level = $this->makeLevel(100.0, 50.0);
        $level->releaseReservation(20.0);
        $this->assertEquals(30.0, $level->getQuantityReserved());
        $this->assertEquals(70.0, $level->getAvailableQuantity());
    }

    public function test_release_reservation_clamps_to_zero(): void
    {
        $level = $this->makeLevel(100.0, 10.0);
        $level->releaseReservation(100.0);
        $this->assertEquals(0.0, $level->getQuantityReserved());
    }

    public function test_release_reservation_rejects_non_positive_quantity(): void
    {
        $level = $this->makeLevel(100.0, 50.0);
        $this->expectException(\InvalidArgumentException::class);
        $level->releaseReservation(0.0);
    }

    public function test_release_reservation_rejects_negative_quantity(): void
    {
        $level = $this->makeLevel(100.0, 50.0);
        $this->expectException(\InvalidArgumentException::class);
        $level->releaseReservation(-5.0);
    }

    // ──────────────────────────────────────────────────────────────────────
    // InventoryLevel – adjust
    // ──────────────────────────────────────────────────────────────────────

    public function test_adjust_sets_exact_quantity_and_returns_diff(): void
    {
        $level = $this->makeLevel(80.0);
        $diff  = $level->adjust(100.0);

        $this->assertEquals(100.0, $level->getQuantityOnHand());
        $this->assertEqualsWithDelta(20.0, $diff, 0.001);
    }

    public function test_adjust_downward_returns_negative_diff(): void
    {
        $level = $this->makeLevel(80.0);
        $diff  = $level->adjust(60.0);

        $this->assertEquals(60.0, $level->getQuantityOnHand());
        $this->assertEqualsWithDelta(-20.0, $diff, 0.001);
    }

    public function test_adjust_to_zero_is_allowed(): void
    {
        $level = $this->makeLevel(50.0);
        $diff  = $level->adjust(0.0);

        $this->assertEquals(0.0, $level->getQuantityOnHand());
        $this->assertEqualsWithDelta(-50.0, $diff, 0.001);
    }

    // ──────────────────────────────────────────────────────────────────────
    // InventoryLevel – getters
    // ──────────────────────────────────────────────────────────────────────

    public function test_inventory_level_in_transit_quantity(): void
    {
        $level = new InventoryLevel(1, 1, 1, 1, null, 100.0, 10.0, 25.0, 'fifo', null, null);
        $this->assertEquals(25.0, $level->getQuantityInTransit());
    }

    public function test_inventory_level_location_id_nullable(): void
    {
        $level = new InventoryLevel(1, 1, 1, 1, null, 100.0, 0.0, 0.0, 'fifo', null, null);
        $this->assertNull($level->getLocationId());

        $level2 = new InventoryLevel(2, 1, 1, 1, 5, 100.0, 0.0, 0.0, 'fifo', null, null);
        $this->assertEquals(5, $level2->getLocationId());
    }

    public function test_inventory_level_valuation_methods(): void
    {
        foreach (['fifo', 'lifo', 'average', 'specific'] as $method) {
            $level = new InventoryLevel(1, 1, 1, 1, null, 100.0, 0.0, 0.0, $method, null, null);
            $this->assertEquals($method, $level->getValuationMethod());
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // InventoryBatch – batch/lot/serial tracking
    // ──────────────────────────────────────────────────────────────────────

    private function makeBatch(
        float $qty = 100.0,
        float $remaining = 100.0,
        string $status = 'active',
        ?\DateTimeInterface $expiresAt = null,
    ): InventoryBatch {
        return new InventoryBatch(
            1, 1, 1, 1,
            'BATCH-001', 'LOT-001', null,
            $qty, $remaining, 5.0,
            new \DateTimeImmutable('2024-01-01'),
            $expiresAt,
            new \DateTimeImmutable('2024-01-15'),
            $status, 'GR-001', null, null,
        );
    }

    public function test_inventory_batch_creation(): void
    {
        $b = $this->makeBatch();
        $this->assertEquals(1, $b->getId());
        $this->assertEquals('BATCH-001', $b->getBatchNumber());
        $this->assertEquals('LOT-001', $b->getLotNumber());
        $this->assertNull($b->getSerialNumber());
        $this->assertEquals(100.0, $b->getQuantity());
        $this->assertEquals(100.0, $b->getQuantityRemaining());
        $this->assertEquals(5.0, $b->getCostPrice());
        $this->assertEquals('active', $b->getStatus());
        $this->assertTrue($b->isActive());
        $this->assertTrue($b->hasStock());
    }

    public function test_inventory_batch_consume_partial(): void
    {
        $b = $this->makeBatch(100.0, 100.0);
        $b->consume(30.0);
        $this->assertEquals(70.0, $b->getQuantityRemaining());
        $this->assertTrue($b->hasStock());
        $this->assertTrue($b->isActive());
    }

    public function test_inventory_batch_consume_all_marks_exhausted(): void
    {
        $b = $this->makeBatch(50.0, 50.0);
        $b->consume(50.0);
        $this->assertEquals(0.0, $b->getQuantityRemaining());
        $this->assertEquals('exhausted', $b->getStatus());
        $this->assertFalse($b->hasStock());
    }

    public function test_inventory_batch_consume_throws_on_overconsumption(): void
    {
        $b = $this->makeBatch(50.0, 20.0);
        $this->expectException(\DomainException::class);
        $b->consume(30.0);  // only 20 remaining
    }

    public function test_inventory_batch_is_expired_by_status(): void
    {
        $b = $this->makeBatch(50.0, 50.0, 'expired');
        $this->assertTrue($b->isExpired());
    }

    public function test_inventory_batch_is_expired_by_date(): void
    {
        $pastDate = new \DateTimeImmutable('2020-01-01');
        $b        = $this->makeBatch(50.0, 50.0, 'active', $pastDate);
        $this->assertTrue($b->isExpired());
    }

    public function test_inventory_batch_is_not_expired_with_future_date(): void
    {
        $futureDate = new \DateTimeImmutable('+1 year');
        $b          = $this->makeBatch(50.0, 50.0, 'active', $futureDate);
        $this->assertFalse($b->isExpired());
    }

    public function test_inventory_batch_no_expiry_date_not_expired(): void
    {
        $b = $this->makeBatch(50.0, 50.0, 'active', null);
        $this->assertFalse($b->isExpired());
    }

    public function test_inventory_batch_serial_number(): void
    {
        $b = new InventoryBatch(
            2, 1, 1, 1, 'SN-BATCH', null, 'SN-12345678',
            1.0, 1.0, 999.99,
            null, null, new \DateTimeImmutable(),
            'active', null, null, null,
        );
        $this->assertEquals('SN-12345678', $b->getSerialNumber());
        $this->assertNull($b->getLotNumber());
    }

    public function test_inventory_batch_reference_and_warehouse(): void
    {
        $b = $this->makeBatch();
        $this->assertEquals(1, $b->getWarehouseId());
        $this->assertEquals('GR-001', $b->getReference());
    }
}
