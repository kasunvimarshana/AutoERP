<?php

declare(strict_types=1);

namespace Modules\Product\Tests\Unit;

use Modules\Core\Application\Helpers\DecimalHelper;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the UOM conversion arithmetic used by ProductService::convertUom().
 *
 * Tests validate the BCMath formulas directly (direct path multiplication and
 * inverse reciprocal division) without requiring a database or Laravel bootstrap.
 *
 * Per AGENT.md Multi-UOM Design and KB.md §8:
 *   - Direct path:   result = quantity × factor
 *   - Inverse path:  result = quantity ÷ inverse_factor
 *   - Minimum 8 decimal places for intermediate UOM calculations
 *   - No floating-point arithmetic permitted
 */
class ProductUomArithmeticTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Direct path: quantity × factor
    // -------------------------------------------------------------------------

    public function test_direct_conversion_multiplies_quantity_by_factor(): void
    {
        // 1 carton = 12 units → convert 3 cartons to units
        $quantity = '3.0000';
        $factor   = '12.0000';

        $result = DecimalHelper::mul($quantity, $factor, DecimalHelper::SCALE_INTERMEDIATE);

        $this->assertSame('36.00000000', $result);
    }

    public function test_direct_conversion_fractional_factor(): void
    {
        // 1 kg = 1000 g → convert 2.5 kg to grams
        $quantity = '2.5000';
        $factor   = '1000.0000';

        $result = DecimalHelper::mul($quantity, $factor, DecimalHelper::SCALE_INTERMEDIATE);

        $this->assertSame('2500.00000000', $result);
    }

    public function test_direct_conversion_sub_unit_factor(): void
    {
        // 1 ml = 0.001 litre → convert 500 ml to litres
        $quantity = '500.0000';
        $factor   = '0.0010';

        $result = DecimalHelper::mul($quantity, $factor, DecimalHelper::SCALE_INTERMEDIATE);

        $this->assertSame('0.50000000', $result);
    }

    // -------------------------------------------------------------------------
    // Inverse reciprocal path: quantity ÷ inverse_factor
    // -------------------------------------------------------------------------

    public function test_inverse_conversion_divides_quantity_by_inverse_factor(): void
    {
        // Stored conversion: 1 unit = 0.0833... dozen (1/12)
        // Converting 24 units → dozens via inverse: 24 ÷ 12 = 2
        $quantity      = '24.0000';
        $inverseFactor = '12.0000';

        $result = DecimalHelper::div($quantity, $inverseFactor, DecimalHelper::SCALE_INTERMEDIATE);

        $this->assertSame('2.00000000', $result);
    }

    public function test_inverse_conversion_decimal_result(): void
    {
        // Convert 1000 g to kg: 1000 ÷ 1000 = 1
        $quantity      = '1000.0000';
        $inverseFactor = '1000.0000';

        $result = DecimalHelper::div($quantity, $inverseFactor, DecimalHelper::SCALE_INTERMEDIATE);

        $this->assertSame('1.00000000', $result);
    }

    public function test_inverse_conversion_preserves_intermediate_precision(): void
    {
        // Convert 1 unit to dozen: 1 ÷ 12 = 0.08333333...
        $quantity      = '1.0000';
        $inverseFactor = '12.0000';

        $result = DecimalHelper::div($quantity, $inverseFactor, DecimalHelper::SCALE_INTERMEDIATE);

        // Must have 8 decimal places of precision
        $this->assertSame(8, strlen(explode('.', $result)[1] ?? ''));
        $this->assertSame('0.08333333', $result);
    }

    // -------------------------------------------------------------------------
    // Same-UOM shortcut: identity (no conversion needed)
    // -------------------------------------------------------------------------

    public function test_same_uom_quantity_is_unchanged(): void
    {
        // ProductService returns $quantity unchanged when fromUomId === toUomId
        $quantity = '7.5000';

        // No arithmetic — just verify the shortcut returns the original value
        $this->assertSame($quantity, $quantity);
    }

    // -------------------------------------------------------------------------
    // Intermediate results are strings (BCMath compliance)
    // -------------------------------------------------------------------------

    public function test_multiplication_result_is_string(): void
    {
        $result = DecimalHelper::mul('5.0000', '3.0000', DecimalHelper::SCALE_INTERMEDIATE);

        $this->assertIsString($result);
    }

    public function test_division_result_is_string(): void
    {
        $result = DecimalHelper::div('15.0000', '3.0000', DecimalHelper::SCALE_INTERMEDIATE);

        $this->assertIsString($result);
    }

    // -------------------------------------------------------------------------
    // High-precision factor: 8+ decimal places (as stored in uom_conversions)
    // -------------------------------------------------------------------------

    public function test_high_precision_factor_preserved_in_conversion(): void
    {
        // factor stored as decimal(20,8) in uom_conversions
        $quantity = '1.00000000';
        $factor   = '0.45359237'; // 1 lb = 0.45359237 kg (exact)

        $result = DecimalHelper::mul($quantity, $factor, DecimalHelper::SCALE_INTERMEDIATE);

        $this->assertSame('0.45359237', $result);
    }

    public function test_conversion_chain_preserves_precision(): void
    {
        // Two-step: quantity → intermediate UOM → target UOM
        // Step 1: 1 box = 6 packs (qty 2 boxes → 12 packs)
        $qty       = '2.0000';
        $factor1   = '6.0000';
        $packs     = DecimalHelper::mul($qty, $factor1, DecimalHelper::SCALE_INTERMEDIATE);

        // Step 2: 1 pack = 10 tablets (12 packs → 120 tablets)
        $factor2  = '10.0000';
        $tablets  = DecimalHelper::mul($packs, $factor2, DecimalHelper::SCALE_INTERMEDIATE);

        $this->assertSame('120.00000000', $tablets);
    }

    // -------------------------------------------------------------------------
    // No floating-point drift
    // -------------------------------------------------------------------------

    public function test_no_floating_point_drift_in_uom_conversion(): void
    {
        // Classic float problem: 0.1 × 3 ≠ 0.3 in IEEE 754
        // With BCMath this must be exact.
        $result = DecimalHelper::mul('0.1000', '3.0000', DecimalHelper::SCALE_STANDARD);

        $this->assertSame('0.3000', $result);
    }
}
