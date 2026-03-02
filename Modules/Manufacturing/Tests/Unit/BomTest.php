<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Tests\Unit;

use Modules\Manufacturing\Domain\Entities\Bom;
use Modules\Manufacturing\Domain\Entities\BomLine;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Bom::scaledComponents — ensures BCMath precision is maintained
 * when scaling BOM component quantities to a given production quantity.
 */
class BomTest extends TestCase
{
    private function makeBom(string $outputQty, array $lines): Bom
    {
        return new Bom(
            id: 1,
            tenantId: 1,
            productId: 10,
            variantId: null,
            outputQuantity: $outputQty,
            reference: 'TEST-BOM',
            isActive: true,
            lines: $lines,
        );
    }

    private function makeLine(int $id, int $productId, string $qty): BomLine
    {
        return new BomLine(
            id: $id,
            bomId: 1,
            componentProductId: $productId,
            componentVariantId: null,
            quantity: $qty,
            notes: null,
        );
    }

    public function test_scaled_components_returns_exact_quantity_when_scale_is_one(): void
    {
        $bom = $this->makeBom('1.0000', [
            $this->makeLine(1, 101, '2.0000'),
            $this->makeLine(2, 102, '0.5000'),
        ]);

        $scaled = $bom->scaledComponents('1.0000');

        $this->assertCount(2, $scaled);
        $this->assertSame('2.0000', $scaled[0]['required_quantity']);
        $this->assertSame('0.5000', $scaled[1]['required_quantity']);
    }

    public function test_scaled_components_doubles_quantities_for_twice_output(): void
    {
        $bom = $this->makeBom('1.0000', [
            $this->makeLine(1, 101, '3.0000'),
        ]);

        $scaled = $bom->scaledComponents('2.0000');

        $this->assertSame('6.0000', $scaled[0]['required_quantity']);
    }

    public function test_scaled_components_handles_fractional_scale(): void
    {
        // BOM outputs 4 units; we produce 1 unit => scale = 0.25
        $bom = $this->makeBom('4.0000', [
            $this->makeLine(1, 101, '8.0000'),
        ]);

        $scaled = $bom->scaledComponents('1.0000');

        // 8 * (1/4) = 2.0000
        $this->assertSame('2.0000', $scaled[0]['required_quantity']);
    }

    public function test_scaled_components_preserves_component_ids(): void
    {
        $bom = $this->makeBom('1.0000', [
            $this->makeLine(1, 201, '1.0000'),
            $this->makeLine(2, 202, '1.0000'),
        ]);

        $scaled = $bom->scaledComponents('3.0000');

        $this->assertSame(201, $scaled[0]['component_product_id']);
        $this->assertSame(202, $scaled[1]['component_product_id']);
    }

    public function test_scaled_components_null_variant_id_is_preserved(): void
    {
        $bom = $this->makeBom('1.0000', [
            $this->makeLine(1, 101, '1.0000'),
        ]);

        $scaled = $bom->scaledComponents('1.0000');

        $this->assertNull($scaled[0]['component_variant_id']);
    }

    public function test_scaled_components_uses_bcmath_precision(): void
    {
        // 1/3 * 3 must be exactly 1.0000, not 0.9999... (float drift)
        $bom = $this->makeBom('3.0000', [
            $this->makeLine(1, 101, '1.0000'),
        ]);

        $scaled = $bom->scaledComponents('3.0000');

        $this->assertSame(0, bccomp($scaled[0]['required_quantity'], '1.0000', 4));
    }
}
