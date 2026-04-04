<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modules\UoM\Domain\ValueObjects\UomType;
use Modules\UoM\Domain\Entities\UomConversion;

class UomModuleTest extends TestCase
{
    // --------------- UomType VO ---------------

    public function test_uom_type_base_constant(): void
    {
        $this->assertSame('base', UomType::BASE);
    }

    public function test_uom_type_purchase_constant(): void
    {
        $this->assertSame('purchase', UomType::PURCHASE);
    }

    public function test_uom_type_sales_constant(): void
    {
        $this->assertSame('sales', UomType::SALES);
    }

    public function test_uom_type_inventory_constant(): void
    {
        $this->assertSame('inventory', UomType::INVENTORY);
    }

    public function test_uom_type_from_base(): void
    {
        $vo = UomType::from(UomType::BASE);
        $this->assertSame('base', (string) $vo);
    }

    public function test_uom_type_from_purchase(): void
    {
        $vo = UomType::from(UomType::PURCHASE);
        $this->assertSame('purchase', (string) $vo);
    }

    public function test_uom_type_from_sales(): void
    {
        $vo = UomType::from(UomType::SALES);
        $this->assertSame('sales', (string) $vo);
    }

    public function test_uom_type_from_inventory(): void
    {
        $vo = UomType::from(UomType::INVENTORY);
        $this->assertSame('inventory', (string) $vo);
    }

    public function test_uom_type_invalid_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        UomType::from('dozen');
    }

    public function test_uom_type_valid_returns_true_for_known(): void
    {
        $this->assertTrue(UomType::valid(UomType::BASE));
    }

    public function test_uom_type_valid_returns_false_for_unknown(): void
    {
        $this->assertFalse(UomType::valid('gallon'));
    }

    // --------------- UomConversion.convert() ---------------

    public function test_uom_conversion_stores_factor(): void
    {
        $conv = new UomConversion(id: 1, fromUomId: 1, toUomId: 2, factor: 12.0);
        $this->assertSame(12.0, $conv->factor);
    }

    public function test_uom_conversion_converts_single_unit(): void
    {
        $conv = new UomConversion(id: null, fromUomId: 1, toUomId: 2, factor: 12.0);
        $this->assertSame(12.0, $conv->convert(1.0));
    }

    public function test_uom_conversion_converts_multiple_units(): void
    {
        $conv = new UomConversion(id: null, fromUomId: 1, toUomId: 2, factor: 12.0);
        $this->assertSame(24.0, $conv->convert(2.0));
    }

    public function test_uom_conversion_with_fractional_factor(): void
    {
        $conv = new UomConversion(id: null, fromUomId: 1, toUomId: 2, factor: 0.5);
        $this->assertSame(2.5, $conv->convert(5.0));
    }

    public function test_uom_conversion_zero_qty_returns_zero(): void
    {
        $conv = new UomConversion(id: null, fromUomId: 1, toUomId: 2, factor: 10.0);
        $this->assertSame(0.0, $conv->convert(0.0));
    }

    public function test_uom_conversion_id_null_allowed(): void
    {
        $conv = new UomConversion(id: null, fromUomId: 1, toUomId: 2, factor: 1.0);
        $this->assertNull($conv->id);
    }

    public function test_uom_conversion_product_id_defaults_null(): void
    {
        $conv = new UomConversion(id: null, fromUomId: 1, toUomId: 2, factor: 1.0);
        $this->assertNull($conv->productId);
    }

    public function test_uom_conversion_product_id_can_be_set(): void
    {
        $conv = new UomConversion(id: null, fromUomId: 1, toUomId: 2, factor: 1.0, productId: 99);
        $this->assertSame(99, $conv->productId);
    }
}
