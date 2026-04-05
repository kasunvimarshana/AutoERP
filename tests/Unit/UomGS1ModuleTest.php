<?php
declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modules\UoM\Domain\Entities\UnitOfMeasure;
use Modules\UoM\Domain\Entities\UomCategory;
use Modules\GS1\Domain\Entities\Gs1Label;

class UomGS1ModuleTest extends TestCase
{
    private function makeUoMCategory(): UomCategory
    {
        return new UomCategory(1, 1, 'Weight', 'weight', true, null, null);
    }
    private function makeUnitOfMeasure(float $factor = 1.0, bool $isBase = true, string $type = 'base'): UnitOfMeasure
    {
        return new UnitOfMeasure(1, 1, 1, 'Kilogram', 'kg', $isBase, $factor, $type, true, null, null);
    }
    private function makeGs1Label(): Gs1Label
    {
        return new Gs1Label(1, 1, 1, 'gtin-13', '5901234123457', 'BATCH-001', null, null, '261231', 1.5, 'LK', null, null);
    }

    // UoM tests
    public function test_uom_category_creation(): void
    {
        $cat = $this->makeUoMCategory();
        $this->assertEquals('Weight', $cat->getName());
        $this->assertEquals(1, $cat->getTenantId());
    }

    public function test_unit_of_measure_creation(): void
    {
        $uom = $this->makeUnitOfMeasure(1.0, true, 'base');
        $this->assertEquals('kg', $uom->getSymbol());
        $this->assertTrue($uom->isBase());
        $this->assertEquals(1.0, $uom->getConversionFactor());
        $this->assertTrue($uom->isActive());
    }

    public function test_unit_of_measure_convert_to_base(): void
    {
        // 1 gram = 0.001 kg (base)
        $gram = new UnitOfMeasure(2, 1, 1, 'Gram', 'g', false, 0.001, 'base', true, null, null);
        $this->assertEquals(1.0, $gram->convertToBase(1000)); // 1000g => 1kg
    }

    public function test_unit_of_measure_convert_from_base(): void
    {
        $gram = new UnitOfMeasure(2, 1, 1, 'Gram', 'g', false, 0.001, 'base', true, null, null);
        $this->assertEquals(1000.0, $gram->convertFromBase(1.0)); // 1kg => 1000g
    }

    // GS1 tests
    public function test_gs1_label_creation(): void
    {
        $label = $this->makeGs1Label();
        $this->assertEquals('gtin-13', $label->getGs1Type());
        $this->assertEquals('5901234123457', $label->getGs1Value());
        $this->assertEquals('BATCH-001', $label->getBatchNumber());
        $this->assertEquals('261231', $label->getExpiryDate());
    }

    public function test_gs1_build_barcode_gtin13(): void
    {
        $label = $this->makeGs1Label();
        $barcode = $label->buildBarcode();
        $this->assertStringContainsString('(01)5901234123457', $barcode);
        $this->assertStringContainsString('(10)BATCH-001', $barcode);
        $this->assertStringContainsString('(17)261231', $barcode);
    }

    public function test_gs1_label_net_weight(): void
    {
        $label = $this->makeGs1Label();
        $this->assertEquals(1.5, $label->getNetWeight());
        $this->assertEquals('LK', $label->getCountryOfOrigin());
    }

    // ──────────────────────────────────────────────────────────────────────
    // UoM – additional tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_uom_category_type(): void
    {
        foreach (['length', 'weight', 'volume', 'time', 'quantity', 'other'] as $type) {
            $cat = new UomCategory(1, 1, 'Cat', $type, true, null, null);
            $this->assertEquals($type, $cat->getType());
        }
    }

    public function test_unit_of_measure_types(): void
    {
        foreach (['base', 'purchase', 'sales', 'inventory'] as $type) {
            $uom = new UnitOfMeasure(1, 1, 1, 'Unit', 'u', true, 1.0, $type, true, null, null);
            $this->assertEquals($type, $uom->getType());
        }
    }

    public function test_unit_of_measure_convert_to_base_gram(): void
    {
        // 500g to kg: 500 * 0.001 = 0.5
        $gram = new UnitOfMeasure(2, 1, 1, 'Gram', 'g', false, 0.001, 'base', true, null, null);
        $this->assertEqualsWithDelta(0.5, $gram->convertToBase(500), 0.0001);
    }

    public function test_unit_of_measure_convert_from_base_gram(): void
    {
        // 0.5 kg to g: 0.5 / 0.001 = 500
        $gram = new UnitOfMeasure(2, 1, 1, 'Gram', 'g', false, 0.001, 'base', true, null, null);
        $this->assertEqualsWithDelta(500.0, $gram->convertFromBase(0.5), 0.0001);
    }

    public function test_uom_convert_handles_zero_factor(): void
    {
        $uom = new UnitOfMeasure(3, 1, 1, 'Bad', 'b', false, 0.0, 'base', false, null, null);
        $this->assertEquals(0.0, $uom->convertFromBase(10.0));
    }

    public function test_unit_of_measure_not_active(): void
    {
        $uom = new UnitOfMeasure(4, 1, 1, 'Old Unit', 'old', false, 1.0, 'base', false, null, null);
        $this->assertFalse($uom->isActive());
        $this->assertFalse($uom->isBase());
    }

    // ──────────────────────────────────────────────────────────────────────
    // GS1 – additional tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_gs1_label_with_serial_number(): void
    {
        $label = new Gs1Label(2, 1, 1, 'gtin-13', '0123456789012', null, null, 'SN-123456', null, null, null, null, null);
        $barcode = $label->buildBarcode();
        $this->assertStringContainsString('(21)SN-123456', $barcode);
        $this->assertStringNotContainsString('(10)', $barcode);  // no batch number
    }

    public function test_gs1_label_with_lot_number(): void
    {
        $label = new Gs1Label(3, 1, 1, 'gtin-13', '5901234123457', null, 'LOT-2024', null, null, null, null, null, null);
        $barcode = $label->buildBarcode();
        $this->assertStringContainsString('(23)LOT-2024', $barcode);
    }

    public function test_gs1_label_minimal_barcode(): void
    {
        // GTIN-13 only, no batch/lot/serial/expiry
        $label = new Gs1Label(4, 1, 1, 'gtin-13', '5901234123457', null, null, null, null, null, null, null, null);
        $barcode = $label->buildBarcode();
        $this->assertEquals('(01)5901234123457', $barcode);
    }

    public function test_gs1_label_non_gtin13_type(): void
    {
        // For gtin-12, the (01) prefix should not be added
        $label = new Gs1Label(5, 1, 1, 'gtin-12', '012345678901', 'BATCH-002', null, null, null, null, null, null, null);
        $barcode = $label->buildBarcode();
        $this->assertStringNotContainsString('(01)', $barcode);  // only gtin-13 adds (01)
        $this->assertStringContainsString('(10)BATCH-002', $barcode);
    }
}
