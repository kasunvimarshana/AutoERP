<?php

declare(strict_types=1);

namespace Modules\Pricing\Tests\Unit;

use Modules\Core\Application\Helpers\DecimalHelper;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the BCMath calculations performed by PricingService.
 *
 * Tests validate the arithmetic formulas used in calculatePrice()
 * (percentage and flat discount resolution) using DecimalHelper directly.
 * No database or Laravel bootstrap required.
 */
class PricingServiceCalculationTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Line total: unit_price × quantity
    // -------------------------------------------------------------------------

    public function test_line_total_calculation(): void
    {
        $unitPrice = '100.0000';
        $quantity  = '5.0000';

        $lineTotal = DecimalHelper::mul($unitPrice, $quantity, DecimalHelper::SCALE_INTERMEDIATE);

        $this->assertSame('500.00000000', $lineTotal);
    }

    // -------------------------------------------------------------------------
    // Percentage discount: (discount_value / 100) × unit_price × quantity
    // -------------------------------------------------------------------------

    public function test_percentage_discount_10_percent(): void
    {
        $unitPrice     = '100.0000';
        $quantity      = '1.0000';
        $discountValue = '10';

        $rate      = DecimalHelper::div($discountValue, '100', DecimalHelper::SCALE_INTERMEDIATE);
        $lineTotal = DecimalHelper::mul($unitPrice, $quantity, DecimalHelper::SCALE_INTERMEDIATE);
        $discount  = DecimalHelper::mul($rate, $lineTotal, DecimalHelper::SCALE_INTERMEDIATE);

        $this->assertSame('10.00000000', $discount);
    }

    public function test_percentage_discount_25_percent_on_multiple_units(): void
    {
        $unitPrice     = '80.0000';
        $quantity      = '4.0000';
        $discountValue = '25';

        // 25 / 100 = 0.25
        $rate = DecimalHelper::div($discountValue, '100', DecimalHelper::SCALE_INTERMEDIATE);
        // 80.0000 × 4.0000 = 320.0000
        $lineTotal = DecimalHelper::mul($unitPrice, $quantity, DecimalHelper::SCALE_INTERMEDIATE);
        // 0.25 × 320.0000 = 80.0000
        $discount = DecimalHelper::mul($rate, $lineTotal, DecimalHelper::SCALE_INTERMEDIATE);

        $this->assertSame('80.00000000', $discount);
    }

    public function test_percentage_discount_rate_uses_intermediate_precision(): void
    {
        // 1/3 as a percentage (33.3333...%) preserves 8 decimal places
        $discountValue = '33.3333';
        $rate          = DecimalHelper::div($discountValue, '100', DecimalHelper::SCALE_INTERMEDIATE);

        // Rate should have 8 decimal places (SCALE_INTERMEDIATE)
        $this->assertSame(8, strlen(explode('.', $rate)[1] ?? ''));
    }

    public function test_zero_percentage_discount_gives_zero(): void
    {
        $unitPrice     = '500.0000';
        $quantity      = '2.0000';
        $discountValue = '0';

        $rate      = DecimalHelper::div($discountValue, '100', DecimalHelper::SCALE_INTERMEDIATE);
        $lineTotal = DecimalHelper::mul($unitPrice, $quantity, DecimalHelper::SCALE_INTERMEDIATE);
        $discount  = DecimalHelper::mul($rate, $lineTotal, DecimalHelper::SCALE_INTERMEDIATE);

        $this->assertTrue(DecimalHelper::equals($discount, '0', DecimalHelper::SCALE_INTERMEDIATE));
    }

    // -------------------------------------------------------------------------
    // Flat discount: fixed amount reduction
    // -------------------------------------------------------------------------

    public function test_flat_discount_applied_directly(): void
    {
        $discountValue = '15.0000';
        $discount      = DecimalHelper::round($discountValue, DecimalHelper::SCALE_INTERMEDIATE);

        $this->assertSame('15.00000000', $discount);
    }

    public function test_flat_discount_preserves_4dp_precision(): void
    {
        $discountValue = '9.9999';
        $discount      = DecimalHelper::round($discountValue, DecimalHelper::SCALE_STANDARD);

        $this->assertSame('9.9999', $discount);
    }

    // -------------------------------------------------------------------------
    // Final price: line_total - discount
    // -------------------------------------------------------------------------

    public function test_final_price_with_percentage_discount(): void
    {
        $unitPrice     = '100.0000';
        $quantity      = '2.0000';
        $discountValue = '10';

        $rate       = DecimalHelper::div($discountValue, '100', DecimalHelper::SCALE_INTERMEDIATE);
        $lineTotal  = DecimalHelper::mul($unitPrice, $quantity, DecimalHelper::SCALE_INTERMEDIATE);
        $discount   = DecimalHelper::mul($rate, $lineTotal, DecimalHelper::SCALE_INTERMEDIATE);
        $finalPrice = DecimalHelper::toMonetary(
            DecimalHelper::sub($lineTotal, $discount, DecimalHelper::SCALE_INTERMEDIATE)
        );

        // 100 × 2 = 200; 10% of 200 = 20; 200 - 20 = 180.00
        $this->assertSame('180.00', $finalPrice);
    }

    public function test_final_price_with_flat_discount(): void
    {
        $unitPrice  = '50.0000';
        $quantity   = '3.0000';
        $flatAmount = '25.0000';

        $lineTotal  = DecimalHelper::mul($unitPrice, $quantity, DecimalHelper::SCALE_INTERMEDIATE);
        $discount   = DecimalHelper::round($flatAmount, DecimalHelper::SCALE_INTERMEDIATE);
        $finalPrice = DecimalHelper::toMonetary(
            DecimalHelper::sub($lineTotal, $discount, DecimalHelper::SCALE_INTERMEDIATE)
        );

        // 50 × 3 = 150; 150 - 25 = 125.00
        $this->assertSame('125.00', $finalPrice);
    }

    public function test_final_price_with_no_discount(): void
    {
        $unitPrice = '200.0000';
        $quantity  = '1.0000';
        $discount  = '0.0000';

        $lineTotal  = DecimalHelper::mul($unitPrice, $quantity, DecimalHelper::SCALE_INTERMEDIATE);
        $finalPrice = DecimalHelper::toMonetary(
            DecimalHelper::sub($lineTotal, $discount, DecimalHelper::SCALE_INTERMEDIATE)
        );

        $this->assertSame('200.00', $finalPrice);
    }

    // -------------------------------------------------------------------------
    // Unit price and discount stored as strings (BCMath compliance)
    // -------------------------------------------------------------------------

    public function test_all_intermediate_results_are_strings(): void
    {
        $unitPrice     = '75.5000';
        $quantity      = '8.0000';
        $discountValue = '5';

        $rate       = DecimalHelper::div($discountValue, '100', DecimalHelper::SCALE_INTERMEDIATE);
        $lineTotal  = DecimalHelper::mul($unitPrice, $quantity, DecimalHelper::SCALE_INTERMEDIATE);
        $discount   = DecimalHelper::mul($rate, $lineTotal, DecimalHelper::SCALE_INTERMEDIATE);
        $finalPrice = DecimalHelper::toMonetary(
            DecimalHelper::sub($lineTotal, $discount, DecimalHelper::SCALE_INTERMEDIATE)
        );

        $this->assertIsString($rate);
        $this->assertIsString($lineTotal);
        $this->assertIsString($discount);
        $this->assertIsString($finalPrice);
    }

    public function test_price_output_is_rounded_to_monetary_scale(): void
    {
        // Verifies toMonetary() produces 2 decimal places regardless of input
        $value = '99.99999';
        $result = DecimalHelper::toMonetary($value);

        $this->assertSame('100.00', $result);
        $parts = explode('.', $result);
        $this->assertSame(2, strlen($parts[1]));
    }
}
