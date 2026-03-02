<?php

declare(strict_types=1);

namespace Modules\Sales\Tests\Unit;

use Modules\Core\Application\Helpers\DecimalHelper;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the BCMath calculations performed by SalesService.
 *
 * Tests validate the arithmetic formulas used in createOrder():
 *   line_total  = (quantity × unit_price) − discount_amount   [intermediate 8dp]
 *   tax         = line_total × tax_rate                        [intermediate 8dp]
 *   subtotal    = Σ line_totals                                [standard 4dp]
 *   total       = subtotal + tax_amount                        [standard 4dp]
 *
 * No database or Laravel bootstrap required.
 */
class SalesServiceLineMathTest extends TestCase
{
    // -------------------------------------------------------------------------
    // line_total = (quantity × unit_price) − discount_amount
    // -------------------------------------------------------------------------

    public function test_line_total_no_discount(): void
    {
        $quantity  = '3.0000';
        $unitPrice = '25.0000';
        $discount  = '0.0000';

        $gross     = DecimalHelper::mul($quantity, $unitPrice, DecimalHelper::SCALE_INTERMEDIATE);
        $lineTotal = DecimalHelper::sub($gross, $discount, DecimalHelper::SCALE_STANDARD);

        $this->assertSame('75.0000', $lineTotal);
    }

    public function test_line_total_with_flat_discount(): void
    {
        $quantity  = '4.0000';
        $unitPrice = '50.0000';
        $discount  = '10.0000';

        $gross     = DecimalHelper::mul($quantity, $unitPrice, DecimalHelper::SCALE_INTERMEDIATE);
        $lineTotal = DecimalHelper::sub($gross, $discount, DecimalHelper::SCALE_STANDARD);

        // 4 × 50 = 200; 200 − 10 = 190
        $this->assertSame('190.0000', $lineTotal);
    }

    public function test_line_total_fractional_unit_price(): void
    {
        $quantity  = '2.0000';
        $unitPrice = '12.5000';
        $discount  = '0.0000';

        $gross     = DecimalHelper::mul($quantity, $unitPrice, DecimalHelper::SCALE_INTERMEDIATE);
        $lineTotal = DecimalHelper::sub($gross, $discount, DecimalHelper::SCALE_STANDARD);

        $this->assertSame('25.0000', $lineTotal);
    }

    // -------------------------------------------------------------------------
    // tax per line = line_total × tax_rate
    // -------------------------------------------------------------------------

    public function test_line_tax_standard_rate(): void
    {
        $lineTotal = '100.0000';
        $taxRate   = '0.1000'; // 10%

        $lineTax = DecimalHelper::mul($lineTotal, $taxRate, DecimalHelper::SCALE_INTERMEDIATE);

        $this->assertSame('10.00000000', $lineTax);
    }

    public function test_line_tax_zero_rate(): void
    {
        $lineTotal = '200.0000';
        $taxRate   = '0.0000';

        $lineTax = DecimalHelper::mul($lineTotal, $taxRate, DecimalHelper::SCALE_INTERMEDIATE);

        $this->assertTrue(DecimalHelper::equals($lineTax, '0', DecimalHelper::SCALE_INTERMEDIATE));
    }

    // -------------------------------------------------------------------------
    // subtotal = Σ line_totals
    // -------------------------------------------------------------------------

    public function test_subtotal_accumulates_correctly(): void
    {
        $lines = [
            ['quantity' => '1.0000', 'unit_price' => '100.0000', 'discount_amount' => '0.0000', 'tax_rate' => '0.0000'],
            ['quantity' => '2.0000', 'unit_price' => '50.0000',  'discount_amount' => '5.0000',  'tax_rate' => '0.0000'],
            ['quantity' => '3.0000', 'unit_price' => '20.0000',  'discount_amount' => '0.0000',  'tax_rate' => '0.0000'],
        ];

        $subtotal = '0';
        foreach ($lines as $line) {
            $gross     = DecimalHelper::mul((string) $line['quantity'], (string) $line['unit_price'], DecimalHelper::SCALE_INTERMEDIATE);
            $lineTotal = DecimalHelper::sub($gross, (string) $line['discount_amount'], DecimalHelper::SCALE_STANDARD);
            $subtotal  = DecimalHelper::add($subtotal, $lineTotal, DecimalHelper::SCALE_STANDARD);
        }

        // 100 + (100 − 5) + 60 = 100 + 95 + 60 = 255
        $this->assertSame('255.0000', $subtotal);
    }

    // -------------------------------------------------------------------------
    // total_amount = subtotal + tax_amount
    // -------------------------------------------------------------------------

    public function test_total_amount_with_tax(): void
    {
        $subtotal  = '255.0000';
        $taxAmount = '25.5000'; // 10% of 255

        $total = DecimalHelper::add($subtotal, $taxAmount, DecimalHelper::SCALE_STANDARD);

        $this->assertSame('280.5000', $total);
    }

    public function test_total_amount_without_tax(): void
    {
        $subtotal  = '150.0000';
        $taxAmount = '0.0000';

        $total = DecimalHelper::add($subtotal, $taxAmount, DecimalHelper::SCALE_STANDARD);

        $this->assertSame('150.0000', $total);
    }

    // -------------------------------------------------------------------------
    // Financial precision: all intermediate and final values are strings
    // -------------------------------------------------------------------------

    public function test_all_arithmetic_results_are_strings(): void
    {
        $quantity  = '5.0000';
        $unitPrice = '19.9900';
        $discount  = '2.0000';
        $taxRate   = '0.2000';

        $gross     = DecimalHelper::mul($quantity, $unitPrice, DecimalHelper::SCALE_INTERMEDIATE);
        $lineTotal = DecimalHelper::sub($gross, $discount, DecimalHelper::SCALE_STANDARD);
        $lineTax   = DecimalHelper::mul($lineTotal, $taxRate, DecimalHelper::SCALE_INTERMEDIATE);
        $total     = DecimalHelper::toMonetary(DecimalHelper::add($lineTotal, $lineTax, DecimalHelper::SCALE_STANDARD));

        $this->assertIsString($gross);
        $this->assertIsString($lineTotal);
        $this->assertIsString($lineTax);
        $this->assertIsString($total);
    }

    public function test_no_floating_point_drift(): void
    {
        // 0.1 + 0.2 must equal 0.3 with BCMath (not 0.30000000000000004 like float)
        $result = DecimalHelper::add('0.1000', '0.2000', DecimalHelper::SCALE_STANDARD);

        $this->assertSame('0.3000', $result);
    }

    public function test_monetary_rounding_on_final_total(): void
    {
        $subtotal  = '99.9999';
        $taxAmount = '9.9999';

        $total       = DecimalHelper::add($subtotal, $taxAmount, DecimalHelper::SCALE_STANDARD);
        $totalRounded = DecimalHelper::toMonetary($total);

        $this->assertSame('110.00', $totalRounded);
        $parts = explode('.', $totalRounded);
        $this->assertCount(2, $parts);
        $this->assertSame(2, strlen($parts[1]));
    }
}
