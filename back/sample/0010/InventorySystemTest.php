<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

// ── Value Object imports
use Modules\Product\Domain\ValueObjects\ProductType;
use Modules\Product\Domain\ValueObjects\UnitOfMeasure;
use Modules\Product\Domain\ValueObjects\ProductStatus;
use Modules\Product\Domain\Entities\Product;
use Modules\Core\Domain\ValueObjects\Money;
use Modules\Core\Domain\ValueObjects\Sku;
use Modules\Core\Domain\ValueObjects\TenantId;
use Modules\Core\Domain\ValueObjects\Quantity;
use Modules\Core\Domain\ValueObjects\Percentage;
use Modules\Inventory\Domain\ValueObjects\ValuationMethod;
use Modules\Inventory\Domain\ValueObjects\StockRotationStrategy;
use Modules\Inventory\Domain\ValueObjects\AllocationAlgorithm;
use Modules\Inventory\Domain\ValueObjects\MovementType;
use Modules\Batch\Domain\ValueObjects\BatchStatus;
use Modules\Batch\Domain\ValueObjects\QcStatus;

/**
 * KV Inventory System — Unit Test Suite
 *
 * Mirrors the KVAutoERP PR #37 test pattern:
 *  - 502 tests total after PR (this suite provides comprehensive coverage)
 *  - Tests domain layer independently (pure PHP, no Laravel bootstrap needed)
 *  - VO construction, validation, predicates, equality, serialization
 *  - Entity construction, mutations, business rule enforcement
 *  - Cross-module value object interactions
 */
class InventorySystemTest extends TestCase
{
    // ═══════════════════════════════════════════════════════════
    // ProductType value object (confirmed from KVAutoERP PR #37)
    // ═══════════════════════════════════════════════════════════

    public function test_product_type_constructs_with_valid_types(): void
    {
        foreach (ProductType::VALID_TYPES as $type) {
            $pt = new ProductType($type);
            $this->assertSame($type, $pt->value());
        }
    }

    public function test_product_type_throws_on_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ProductType('banana');
    }

    public function test_product_type_predicates(): void
    {
        $this->assertTrue((new ProductType('physical'))->isPhysical());
        $this->assertTrue((new ProductType('service'))->isService());
        $this->assertTrue((new ProductType('digital'))->isDigital());
        $this->assertTrue((new ProductType('subscription'))->isSubscription());
        $this->assertTrue((new ProductType('combo'))->isCombo());
        $this->assertTrue((new ProductType('variable'))->isVariable());
        $this->assertTrue((new ProductType('raw_material'))->isRawMaterial());
        $this->assertTrue((new ProductType('finished_good'))->isFinishedGood());
        $this->assertTrue((new ProductType('wip'))->isWip());
        $this->assertTrue((new ProductType('kit'))->isKit());
    }

    public function test_product_type_stockable(): void
    {
        $this->assertTrue((new ProductType('physical'))->isStockable());
        $this->assertFalse((new ProductType('service'))->isStockable());
        $this->assertFalse((new ProductType('digital'))->isStockable());
    }

    public function test_product_type_composite(): void
    {
        $this->assertTrue((new ProductType('combo'))->isComposite());
        $this->assertTrue((new ProductType('kit'))->isComposite());
        $this->assertFalse((new ProductType('physical'))->isComposite());
    }

    public function test_product_type_equals(): void
    {
        $a = new ProductType('physical');
        $b = new ProductType('physical');
        $c = new ProductType('service');
        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function test_product_type_to_string(): void
    {
        $this->assertSame('physical', (string) new ProductType('physical'));
    }

    // ═══════════════════════════════════════════════════════════
    // UnitOfMeasure value object (confirmed from KVAutoERP PR #37)
    // ═══════════════════════════════════════════════════════════

    public function test_uom_constructs_with_valid_types(): void
    {
        foreach (UnitOfMeasure::VALID_TYPES as $type) {
            $uom = new UnitOfMeasure('box', $type, 12.0);
            $this->assertSame($type, $uom->type());
        }
    }

    public function test_uom_throws_on_invalid_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new UnitOfMeasure('box', 'invalid_type', 1.0);
    }

    public function test_uom_throws_on_empty_unit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new UnitOfMeasure('', 'buying', 1.0);
    }

    public function test_uom_throws_on_zero_conversion_factor(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new UnitOfMeasure('box', 'buying', 0.0);
    }

    public function test_uom_predicates(): void
    {
        $this->assertTrue((new UnitOfMeasure('box', 'buying', 12))->isBuying());
        $this->assertTrue((new UnitOfMeasure('pcs', 'selling', 1))->isSelling());
        $this->assertTrue((new UnitOfMeasure('pcs', 'inventory', 1))->isInventory());
        $this->assertTrue((new UnitOfMeasure('kg', 'production', 1))->isProduction());
        $this->assertTrue((new UnitOfMeasure('pallet', 'shipping', 48))->isShipping());
    }

    public function test_uom_conversion(): void
    {
        $uom = new UnitOfMeasure('box', 'buying', 12.0);
        $this->assertEqualsWithDelta(24.0, $uom->toBaseQuantity(2.0), 0.0001);
        $this->assertEqualsWithDelta(2.0, $uom->fromBaseQuantity(24.0), 0.0001);
    }

    public function test_uom_to_array_round_trip(): void
    {
        $original = new UnitOfMeasure('box', 'buying', 12.0);
        $arr      = $original->toArray();
        $restored = UnitOfMeasure::fromArray($arr);

        $this->assertTrue($original->equals($restored));
    }

    public function test_uom_from_array_missing_conversion_factor_defaults_to_one(): void
    {
        $uom = UnitOfMeasure::fromArray(['unit' => 'pcs', 'type' => 'selling']);
        $this->assertEqualsWithDelta(1.0, $uom->conversionFactor(), 0.0001);
    }

    // ═══════════════════════════════════════════════════════════
    // ProductStatus value object
    // ═══════════════════════════════════════════════════════════

    public function test_product_status_valid(): void
    {
        foreach (ProductStatus::VALID as $s) {
            $status = new ProductStatus($s);
            $this->assertSame($s, $status->value());
        }
    }

    public function test_product_status_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ProductStatus('banana');
    }

    public function test_product_status_sellable(): void
    {
        $this->assertTrue((new ProductStatus('active'))->isSellable());
        $this->assertFalse((new ProductStatus('draft'))->isSellable());
        $this->assertFalse((new ProductStatus('discontinued'))->isSellable());
    }

    // ═══════════════════════════════════════════════════════════
    // Money value object
    // ═══════════════════════════════════════════════════════════

    public function test_money_constructs(): void
    {
        $m = new Money(100.0, 'USD');
        $this->assertEqualsWithDelta(100.0, $m->amount(), 0.00001);
        $this->assertSame('USD', $m->currency());
    }

    public function test_money_rejects_negative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Money(-1.0, 'USD');
    }

    public function test_money_rejects_invalid_currency(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Money(10.0, 'US');
    }

    public function test_money_add(): void
    {
        $a = new Money(100.0, 'USD');
        $b = new Money(50.0, 'USD');
        $this->assertEqualsWithDelta(150.0, $a->add($b)->amount(), 0.00001);
    }

    public function test_money_add_different_currency_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new Money(100.0, 'USD'))->add(new Money(50.0, 'EUR'));
    }

    public function test_money_multiply(): void
    {
        $m = new Money(10.0, 'USD');
        $this->assertEqualsWithDelta(25.0, $m->multiply(2.5)->amount(), 0.00001);
    }

    public function test_money_to_array_round_trip(): void
    {
        $m = new Money(99.99, 'GBP');
        $restored = Money::fromArray($m->toArray());
        $this->assertTrue($m->equals($restored));
    }

    // ═══════════════════════════════════════════════════════════
    // Sku value object
    // ═══════════════════════════════════════════════════════════

    public function test_sku_uppercases(): void
    {
        $sku = new Sku('abc-123');
        $this->assertSame('ABC-123', $sku->value());
    }

    public function test_sku_rejects_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Sku('');
    }

    public function test_sku_rejects_spaces(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Sku('ABC 123');
    }

    public function test_sku_rejects_over_100_chars(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Sku(str_repeat('A', 101));
    }

    // ═══════════════════════════════════════════════════════════
    // TenantId value object
    // ═══════════════════════════════════════════════════════════

    public function test_tenant_id_valid(): void
    {
        $t = new TenantId(42);
        $this->assertSame(42, $t->value());
    }

    public function test_tenant_id_rejects_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TenantId(0);
    }

    public function test_tenant_id_rejects_negative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TenantId(-1);
    }

    // ═══════════════════════════════════════════════════════════
    // Quantity value object
    // ═══════════════════════════════════════════════════════════

    public function test_quantity_add(): void
    {
        $a = new Quantity(10.5, 'pcs');
        $b = new Quantity(5.0, 'pcs');
        $this->assertEqualsWithDelta(15.5, $a->add($b)->value(), 0.0001);
    }

    public function test_quantity_subtract(): void
    {
        $a = new Quantity(10.0, 'kg');
        $b = new Quantity(3.5, 'kg');
        $this->assertEqualsWithDelta(6.5, $a->subtract($b)->value(), 0.0001);
    }

    public function test_quantity_different_units_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new Quantity(10.0, 'kg'))->add(new Quantity(5.0, 'pcs'));
    }

    public function test_quantity_is_negative(): void
    {
        $this->assertTrue((new Quantity(-1.0, 'pcs'))->isNegative());
        $this->assertFalse((new Quantity(1.0, 'pcs'))->isNegative());
    }

    // ═══════════════════════════════════════════════════════════
    // Percentage value object
    // ═══════════════════════════════════════════════════════════

    public function test_percentage_as_decimal(): void
    {
        $this->assertEqualsWithDelta(0.2, (new Percentage(20))->asDecimal(), 0.00001);
    }

    public function test_percentage_rejects_over_100(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Percentage(101);
    }

    public function test_percentage_rejects_negative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Percentage(-1);
    }

    // ═══════════════════════════════════════════════════════════
    // ValuationMethod value object
    // ═══════════════════════════════════════════════════════════

    public function test_valuation_method_all_valid(): void
    {
        foreach (ValuationMethod::VALID as $m) {
            $vm = new ValuationMethod($m);
            $this->assertSame($m, $vm->value());
        }
    }

    public function test_valuation_method_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ValuationMethod('INVALID');
    }

    public function test_valuation_method_layer_based(): void
    {
        $this->assertTrue((new ValuationMethod('FIFO'))->isLayerBased());
        $this->assertTrue((new ValuationMethod('LIFO'))->isLayerBased());
        $this->assertTrue((new ValuationMethod('FEFO'))->isLayerBased());
        $this->assertFalse((new ValuationMethod('AVCO'))->isLayerBased());
        $this->assertFalse((new ValuationMethod('standard_cost'))->isLayerBased());
        $this->assertFalse((new ValuationMethod('retail'))->isLayerBased());
    }

    public function test_valuation_method_predicates(): void
    {
        $this->assertTrue((new ValuationMethod('FIFO'))->isFifo());
        $this->assertTrue((new ValuationMethod('LIFO'))->isLifo());
        $this->assertTrue((new ValuationMethod('AVCO'))->isAvco());
        $this->assertTrue((new ValuationMethod('FEFO'))->isFefo());
        $this->assertTrue((new ValuationMethod('FMFO'))->isFmfo());
        $this->assertTrue((new ValuationMethod('specific_id'))->isSpecificId());
        $this->assertTrue((new ValuationMethod('standard_cost'))->isStandardCost());
        $this->assertTrue((new ValuationMethod('retail'))->isRetail());
    }

    // ═══════════════════════════════════════════════════════════
    // StockRotationStrategy value object
    // ═══════════════════════════════════════════════════════════

    public function test_rotation_strategy_all_valid(): void
    {
        foreach (StockRotationStrategy::VALID as $s) {
            $rs = new StockRotationStrategy($s);
            $this->assertSame($s, $rs->value());
        }
    }

    public function test_rotation_strategy_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new StockRotationStrategy('INVALID');
    }

    public function test_rotation_strategy_predicates(): void
    {
        $this->assertTrue((new StockRotationStrategy('FIFO'))->isFifo());
        $this->assertTrue((new StockRotationStrategy('LIFO'))->isLifo());
        $this->assertTrue((new StockRotationStrategy('FEFO'))->isFefo());
        $this->assertTrue((new StockRotationStrategy('FMFO'))->isFmfo());
        $this->assertTrue((new StockRotationStrategy('LEFO'))->isLefo());
    }

    // ═══════════════════════════════════════════════════════════
    // AllocationAlgorithm value object
    // ═══════════════════════════════════════════════════════════

    public function test_allocation_algorithm_all_valid(): void
    {
        foreach (AllocationAlgorithm::VALID as $a) {
            $algo = new AllocationAlgorithm($a);
            $this->assertSame($a, $algo->value());
        }
    }

    public function test_allocation_algorithm_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AllocationAlgorithm('random_algo');
    }

    public function test_allocation_algorithm_is_picking(): void
    {
        $this->assertTrue((new AllocationAlgorithm('wave_picking'))->isPickingAlgorithm());
        $this->assertTrue((new AllocationAlgorithm('zone_picking'))->isPickingAlgorithm());
        $this->assertTrue((new AllocationAlgorithm('batch_picking'))->isPickingAlgorithm());
        $this->assertTrue((new AllocationAlgorithm('cluster_picking'))->isPickingAlgorithm());
        $this->assertFalse((new AllocationAlgorithm('soft_reservation'))->isPickingAlgorithm());
        $this->assertFalse((new AllocationAlgorithm('fair_share'))->isPickingAlgorithm());
    }

    // ═══════════════════════════════════════════════════════════
    // BatchStatus and QcStatus value objects
    // ═══════════════════════════════════════════════════════════

    public function test_batch_status_usable(): void
    {
        $this->assertTrue((new BatchStatus('active'))->isUsable());
        $this->assertFalse((new BatchStatus('quarantine'))->isUsable());
        $this->assertFalse((new BatchStatus('hold'))->isUsable());
        $this->assertFalse((new BatchStatus('rejected'))->isUsable());
    }

    public function test_batch_status_blocked(): void
    {
        $this->assertTrue((new BatchStatus('quarantine'))->isBlocked());
        $this->assertTrue((new BatchStatus('hold'))->isBlocked());
        $this->assertFalse((new BatchStatus('active'))->isBlocked());
    }

    public function test_qc_status_approved(): void
    {
        $this->assertTrue((new QcStatus('passed'))->isApproved());
        $this->assertTrue((new QcStatus('waived'))->isApproved());
        $this->assertFalse((new QcStatus('pending'))->isApproved());
        $this->assertFalse((new QcStatus('failed'))->isApproved());
    }

    // ═══════════════════════════════════════════════════════════
    // Product domain entity
    // ═══════════════════════════════════════════════════════════

    private function makeProduct(array $overrides = []): Product
    {
        return new Product(
            tenantId: $overrides['tenantId'] ?? 1,
            sku:      $overrides['sku']      ?? 'TEST-SKU-001',
            name:     $overrides['name']     ?? 'Test Product',
            price:    $overrides['price']    ?? 99.99,
            currency: $overrides['currency'] ?? 'USD',
            type:     $overrides['type']     ?? ProductType::PHYSICAL,
            status:   $overrides['status']   ?? ProductStatus::ACTIVE,
        );
    }

    public function test_product_constructs_with_defaults(): void
    {
        $p = $this->makeProduct();
        $this->assertSame('TEST-SKU-001', (string) $p->getSku());
        $this->assertSame('Test Product', $p->getName());
        $this->assertSame('physical', (string) $p->getType());
        $this->assertSame('active', (string) $p->getStatus());
        $this->assertNull($p->getId());
        $this->assertEmpty($p->getUnitsOfMeasure());
        $this->assertFalse($p->isTrackBatches());
        $this->assertFalse($p->isTrackLots());
        $this->assertFalse($p->isTrackSerials());
        $this->assertFalse($p->isTrackExpiry());
    }

    public function test_product_with_units_of_measure(): void
    {
        $p = new Product(
            tenantId: 1, sku: 'SKU-001', name: 'Product', price: 10.0,
            unitsOfMeasure: [
                ['unit' => 'box',  'type' => 'buying',    'conversion_factor' => 12],
                ['unit' => 'pcs',  'type' => 'selling',   'conversion_factor' => 1],
                ['unit' => 'pcs',  'type' => 'inventory', 'conversion_factor' => 1],
            ],
        );

        $this->assertCount(3, $p->getUnitsOfMeasure());
        $this->assertNotNull($p->getBuyingUnit());
        $this->assertNotNull($p->getSellingUnit());
        $this->assertNotNull($p->getInventoryUnit());
        $this->assertSame('box', $p->getBuyingUnit()->unit());
        $this->assertEqualsWithDelta(12.0, $p->getBuyingUnit()->conversionFactor(), 0.0001);
        $this->assertTrue($p->hasUomForType('buying'));
        $this->assertFalse($p->hasUomForType('shipping'));
    }

    public function test_product_update_details_preserves_uom_when_null(): void
    {
        $p = new Product(
            tenantId: 1, sku: 'SKU-001', name: 'Old Name', price: 10.0,
            unitsOfMeasure: [['unit' => 'box', 'type' => 'buying', 'conversion_factor' => 12]],
        );

        // null = preserve existing (confirmed PR #37 pattern)
        $p->updateDetails(name: 'New Name', price: 20.0, unitsOfMeasure: null);
        $this->assertCount(1, $p->getUnitsOfMeasure());
        $this->assertSame('New Name', $p->getName());
    }

    public function test_product_update_details_clears_uom_when_empty_array(): void
    {
        $p = new Product(
            tenantId: 1, sku: 'SKU-001', name: 'Product', price: 10.0,
            unitsOfMeasure: [['unit' => 'box', 'type' => 'buying', 'conversion_factor' => 12]],
        );

        // [] = explicitly clear (confirmed PR #37 pattern)
        $p->updateDetails(name: 'Product', price: 10.0, unitsOfMeasure: []);
        $this->assertEmpty($p->getUnitsOfMeasure());
    }

    public function test_product_change_type(): void
    {
        $p = $this->makeProduct(['type' => 'physical']);
        $p->updateDetails(name: 'P', price: 10.0, type: 'service');
        $this->assertSame('service', (string) $p->getType());
        $this->assertFalse($p->isStockable());
    }

    public function test_product_change_status(): void
    {
        $p = $this->makeProduct();
        $p->changeStatus('inactive');
        $this->assertFalse($p->isActive());
        $this->assertFalse($p->isSellable());
    }

    public function test_product_configure_tracking(): void
    {
        $p = $this->makeProduct();
        $p->configureTracking(batches: true, lots: true, serials: false, expiry: true);
        $this->assertTrue($p->isTrackBatches());
        $this->assertTrue($p->isTrackLots());
        $this->assertFalse($p->isTrackSerials());
        $this->assertTrue($p->isTrackExpiry());
    }

    public function test_product_assign_id_once(): void
    {
        $p = $this->makeProduct();
        $p->assignId(42);
        $this->assertSame(42, $p->getId());
    }

    public function test_product_assign_id_twice_throws(): void
    {
        $this->expectException(\LogicException::class);
        $p = $this->makeProduct();
        $p->assignId(1);
        $p->assignId(2);
    }

    public function test_product_service_type_not_stockable(): void
    {
        $p = $this->makeProduct(['type' => 'service']);
        $this->assertFalse($p->isStockable());
    }

    public function test_product_digital_type_not_stockable(): void
    {
        $p = $this->makeProduct(['type' => 'digital']);
        $this->assertFalse($p->isStockable());
    }

    public function test_product_combo_type_is_composite(): void
    {
        $p = $this->makeProduct(['type' => 'combo']);
        $this->assertTrue($p->getType()->isComposite());
        $this->assertTrue($p->isStockable());
    }

    public function test_product_kit_type(): void
    {
        $p = $this->makeProduct(['type' => 'kit']);
        $this->assertTrue($p->getType()->isKit());
        $this->assertTrue($p->getType()->isComposite());
    }

    public function test_product_reorder_update(): void
    {
        $p = $this->makeProduct();
        $p->updateReorderConfig(reorderPoint: 100.0, safetyStock: 20.0, leadTimeDays: 7);
        $this->assertEqualsWithDelta(100.0, $p->getReorderPoint(), 0.001);
        $this->assertEqualsWithDelta(20.0, $p->getSafetyStock(), 0.001);
        $this->assertSame(7, $p->getLeadTimeDays());
    }

    // ═══════════════════════════════════════════════════════════
    // MovementType — direction resolution
    // ═══════════════════════════════════════════════════════════

    public function test_movement_type_direction_in(): void
    {
        foreach (MovementType::INBOUND_TYPES as $type) {
            $this->assertSame('IN', MovementType::direction($type), "Expected IN for {$type}");
        }
    }

    public function test_movement_type_direction_out(): void
    {
        foreach (MovementType::OUTBOUND_TYPES as $type) {
            $this->assertSame('OUT', MovementType::direction($type), "Expected OUT for {$type}");
        }
    }
}
