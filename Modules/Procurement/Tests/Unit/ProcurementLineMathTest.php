<?php

declare(strict_types=1);

namespace Modules\Procurement\Tests\Unit;

use Modules\Core\Application\Helpers\DecimalHelper;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the BCMath calculations performed by ProcurementService.
 *
 * Tests validate the arithmetic formulas used in createPurchaseOrder()
 * and threeWayMatch() using DecimalHelper directly — no database required.
 */
class ProcurementLineMathTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Line total: quantity × unit_cost
    // -------------------------------------------------------------------------

    public function test_line_total_whole_numbers(): void
    {
        $qty      = '10.0000';
        $unitCost = '25.0000';

        $lineTotal = DecimalHelper::round(
            DecimalHelper::mul($qty, $unitCost, DecimalHelper::SCALE_INTERMEDIATE),
            DecimalHelper::SCALE_STANDARD
        );

        $this->assertSame('250.0000', $lineTotal);
    }

    public function test_line_total_fractional_unit_cost(): void
    {
        $qty      = '4.0000';
        $unitCost = '12.5000';

        $lineTotal = DecimalHelper::round(
            DecimalHelper::mul($qty, $unitCost, DecimalHelper::SCALE_INTERMEDIATE),
            DecimalHelper::SCALE_STANDARD
        );

        $this->assertSame('50.0000', $lineTotal);
    }

    public function test_line_total_small_quantities(): void
    {
        $qty      = '0.0100';
        $unitCost = '1000.0000';

        $lineTotal = DecimalHelper::round(
            DecimalHelper::mul($qty, $unitCost, DecimalHelper::SCALE_INTERMEDIATE),
            DecimalHelper::SCALE_STANDARD
        );

        $this->assertSame('10.0000', $lineTotal);
    }

    // -------------------------------------------------------------------------
    // Subtotal: sum of line totals
    // -------------------------------------------------------------------------

    public function test_subtotal_single_line(): void
    {
        $subtotal = '0';
        $lines    = [
            ['quantity' => '5.0000', 'unit_cost' => '20.0000'],
        ];

        foreach ($lines as $line) {
            $lineTotal = DecimalHelper::round(
                DecimalHelper::mul($line['quantity'], $line['unit_cost'], DecimalHelper::SCALE_INTERMEDIATE),
                DecimalHelper::SCALE_STANDARD
            );
            $subtotal = DecimalHelper::add($subtotal, $lineTotal, DecimalHelper::SCALE_STANDARD);
        }

        $this->assertSame('100.0000', $subtotal);
    }

    public function test_subtotal_multiple_lines(): void
    {
        $subtotal = '0';
        $lines    = [
            ['quantity' => '2.0000', 'unit_cost' => '50.0000'],   // 100.0000
            ['quantity' => '3.0000', 'unit_cost' => '10.0000'],   // 30.0000
            ['quantity' => '1.0000', 'unit_cost' => '250.0000'],  // 250.0000
        ];

        foreach ($lines as $line) {
            $lineTotal = DecimalHelper::round(
                DecimalHelper::mul($line['quantity'], $line['unit_cost'], DecimalHelper::SCALE_INTERMEDIATE),
                DecimalHelper::SCALE_STANDARD
            );
            $subtotal = DecimalHelper::add($subtotal, $lineTotal, DecimalHelper::SCALE_STANDARD);
        }

        $this->assertSame('380.0000', $subtotal);
    }

    // -------------------------------------------------------------------------
    // Total amount: subtotal + tax_amount
    // -------------------------------------------------------------------------

    public function test_total_amount_with_zero_tax(): void
    {
        $subtotal    = '380.0000';
        $taxAmount   = '0.0000';
        $totalAmount = DecimalHelper::add($subtotal, $taxAmount, DecimalHelper::SCALE_STANDARD);

        $this->assertSame('380.0000', $totalAmount);
    }

    public function test_total_amount_with_tax(): void
    {
        $subtotal    = '200.0000';
        $taxAmount   = '20.0000';
        $totalAmount = DecimalHelper::add($subtotal, $taxAmount, DecimalHelper::SCALE_STANDARD);

        $this->assertSame('220.0000', $totalAmount);
    }

    // -------------------------------------------------------------------------
    // Three-way match: quantity comparison
    // -------------------------------------------------------------------------

    public function test_three_way_match_quantities_equal(): void
    {
        $ordered  = '100.0000';
        $received = '100.0000';

        $this->assertTrue(DecimalHelper::equals($ordered, $received));
    }

    public function test_three_way_match_quantities_discrepancy(): void
    {
        $ordered  = '100.0000';
        $received = '95.0000';

        $this->assertFalse(DecimalHelper::equals($ordered, $received));
    }

    public function test_three_way_match_received_sum_across_multiple_receipts(): void
    {
        // Simulates summing received quantities across two separate receipts
        $receivedQty = '0';
        $receipts    = ['60.0000', '40.0000'];

        foreach ($receipts as $receiptQty) {
            $receivedQty = DecimalHelper::add($receivedQty, $receiptQty, DecimalHelper::SCALE_STANDARD);
        }

        $ordered = '100.0000';
        $this->assertTrue(DecimalHelper::equals($ordered, $receivedQty));
    }

    public function test_three_way_match_billed_total_vs_po_total(): void
    {
        $poTotal   = '500.0000';
        $billTotal = '500.0000';

        $this->assertTrue(DecimalHelper::equals($poTotal, $billTotal));
    }

    public function test_three_way_match_billed_total_discrepancy(): void
    {
        $poTotal   = '500.0000';
        $billTotal = '480.0000';

        $this->assertFalse(DecimalHelper::equals($poTotal, $billTotal));
    }

    // -------------------------------------------------------------------------
    // BCMath precision guarantee (no floating-point drift)
    // -------------------------------------------------------------------------

    public function test_line_total_no_floating_point_drift(): void
    {
        // Classic IEEE-754 drift: 0.1 * 3 is not exactly 0.3 in float
        $qty      = '3.0000';
        $unitCost = '0.1000';

        $lineTotal = DecimalHelper::round(
            DecimalHelper::mul($qty, $unitCost, DecimalHelper::SCALE_INTERMEDIATE),
            DecimalHelper::SCALE_STANDARD
        );

        $this->assertSame('0.3000', $lineTotal);
        $this->assertIsString($lineTotal);
    }

    public function test_large_quantity_precision(): void
    {
        $qty      = '9999.9999';
        $unitCost = '9999.9999';

        $lineTotal = DecimalHelper::round(
            DecimalHelper::mul($qty, $unitCost, DecimalHelper::SCALE_INTERMEDIATE),
            DecimalHelper::SCALE_STANDARD
        );

        // 9999.9999 × 9999.9999 = 99999998.00000001 → rounds to 99999998.0000 at 4dp
        $this->assertSame('99999998.0000', $lineTotal);
        $this->assertIsString($lineTotal);
    }
}
