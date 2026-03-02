<?php

declare(strict_types=1);

namespace Modules\POS\Tests\Unit;

use Modules\Core\Application\Helpers\DecimalHelper;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the BCMath calculations performed by POSService.
 *
 * Tests validate the arithmetic formulas used in createTransaction():
 *   line_total   = (quantity × unit_price) − discount_amount     [intermediate 8dp]
 *   subtotal     = Σ line_totals                                  [standard 4dp]
 *   total_amount = subtotal − order_discount                      [standard 4dp]
 *   paid_amount  = Σ payment amounts                              [standard 4dp]
 *   change_due   = paid_amount − total_amount (when paid >= total)[standard 4dp]
 *
 * No database or Laravel bootstrap required.
 */
class POSServiceLineMathTest extends TestCase
{
    // -------------------------------------------------------------------------
    // line_total = (quantity × unit_price) − discount_amount
    // -------------------------------------------------------------------------

    public function test_line_total_no_discount(): void
    {
        $quantity  = '2.0000';
        $unitPrice = '15.0000';
        $discount  = '0.0000';

        $gross     = DecimalHelper::mul($quantity, $unitPrice, DecimalHelper::SCALE_INTERMEDIATE);
        $lineTotal = DecimalHelper::sub($gross, $discount, DecimalHelper::SCALE_STANDARD);

        $this->assertSame('30.0000', $lineTotal);
    }

    public function test_line_total_with_item_discount(): void
    {
        $quantity  = '1.0000';
        $unitPrice = '99.9900';
        $discount  = '9.9900';

        $gross     = DecimalHelper::mul($quantity, $unitPrice, DecimalHelper::SCALE_INTERMEDIATE);
        $lineTotal = DecimalHelper::sub($gross, $discount, DecimalHelper::SCALE_STANDARD);

        $this->assertSame('90.0000', $lineTotal);
    }

    // -------------------------------------------------------------------------
    // subtotal = Σ line_totals
    // -------------------------------------------------------------------------

    public function test_subtotal_two_lines(): void
    {
        $lines = [
            ['quantity' => '1.0000', 'unit_price' => '10.0000', 'discount_amount' => '0.0000'],
            ['quantity' => '2.0000', 'unit_price' => '5.0000',  'discount_amount' => '1.0000'],
        ];

        $subtotal = '0';
        foreach ($lines as $line) {
            $gross     = DecimalHelper::mul((string) $line['quantity'], (string) $line['unit_price'], DecimalHelper::SCALE_INTERMEDIATE);
            $lineTotal = DecimalHelper::sub($gross, (string) $line['discount_amount'], DecimalHelper::SCALE_STANDARD);
            $subtotal  = DecimalHelper::add($subtotal, $lineTotal, DecimalHelper::SCALE_STANDARD);
        }

        // line1: 1×10 = 10; line2: 2×5 − 1 = 9; total = 19
        $this->assertSame('19.0000', $subtotal);
    }

    // -------------------------------------------------------------------------
    // total_amount = subtotal − order_discount
    // -------------------------------------------------------------------------

    public function test_total_amount_with_order_discount(): void
    {
        $subtotal       = '100.0000';
        $orderDiscount  = '5.0000';

        $total = DecimalHelper::sub($subtotal, $orderDiscount, DecimalHelper::SCALE_STANDARD);

        $this->assertSame('95.0000', $total);
    }

    public function test_total_amount_without_order_discount(): void
    {
        $subtotal      = '50.0000';
        $orderDiscount = '0.0000';

        $total = DecimalHelper::sub($subtotal, $orderDiscount, DecimalHelper::SCALE_STANDARD);

        $this->assertSame('50.0000', $total);
    }

    // -------------------------------------------------------------------------
    // paid_amount = Σ payments
    // -------------------------------------------------------------------------

    public function test_paid_amount_single_payment(): void
    {
        $paidAmount = '0';
        $paidAmount = DecimalHelper::add($paidAmount, '50.0000', DecimalHelper::SCALE_STANDARD);

        $this->assertSame('50.0000', $paidAmount);
    }

    public function test_paid_amount_split_payment(): void
    {
        $paidAmount = '0';
        $paidAmount = DecimalHelper::add($paidAmount, '30.0000', DecimalHelper::SCALE_STANDARD);
        $paidAmount = DecimalHelper::add($paidAmount, '20.0000', DecimalHelper::SCALE_STANDARD);

        $this->assertSame('50.0000', $paidAmount);
    }

    // -------------------------------------------------------------------------
    // change_due = paid_amount − total (only when paid >= total)
    // -------------------------------------------------------------------------

    public function test_change_due_when_overpaid(): void
    {
        $totalAmount = '45.0000';
        $paidAmount  = '50.0000';

        $changeDue = DecimalHelper::greaterThanOrEqual($paidAmount, $totalAmount)
            ? DecimalHelper::sub($paidAmount, $totalAmount, DecimalHelper::SCALE_STANDARD)
            : '0.0000';

        $this->assertSame('5.0000', $changeDue);
    }

    public function test_change_due_exact_payment_is_zero(): void
    {
        $totalAmount = '50.0000';
        $paidAmount  = '50.0000';

        $changeDue = DecimalHelper::greaterThanOrEqual($paidAmount, $totalAmount)
            ? DecimalHelper::sub($paidAmount, $totalAmount, DecimalHelper::SCALE_STANDARD)
            : '0.0000';

        $this->assertSame('0.0000', $changeDue);
    }

    public function test_change_due_underpayment_is_zero(): void
    {
        $totalAmount = '50.0000';
        $paidAmount  = '40.0000';

        $changeDue = DecimalHelper::greaterThanOrEqual($paidAmount, $totalAmount)
            ? DecimalHelper::sub($paidAmount, $totalAmount, DecimalHelper::SCALE_STANDARD)
            : '0.0000';

        $this->assertSame('0.0000', $changeDue);
    }

    // -------------------------------------------------------------------------
    // Financial precision
    // -------------------------------------------------------------------------

    public function test_all_arithmetic_results_are_strings(): void
    {
        $quantity  = '3.0000';
        $unitPrice = '12.5000';
        $discount  = '1.0000';

        $gross     = DecimalHelper::mul($quantity, $unitPrice, DecimalHelper::SCALE_INTERMEDIATE);
        $lineTotal = DecimalHelper::sub($gross, $discount, DecimalHelper::SCALE_STANDARD);

        $this->assertIsString($gross);
        $this->assertIsString($lineTotal);
    }

    public function test_no_floating_point_drift(): void
    {
        // 0.1 + 0.2 must equal 0.3000 — not 0.30000000000000004
        $result = DecimalHelper::add('0.1000', '0.2000', DecimalHelper::SCALE_STANDARD);

        $this->assertSame('0.3000', $result);
    }

    public function test_monetary_rounding_on_change_due(): void
    {
        $paidAmount  = '50.0050';
        $totalAmount = '50.0000';

        $changeDue = DecimalHelper::sub($paidAmount, $totalAmount, DecimalHelper::SCALE_STANDARD);
        $rounded   = DecimalHelper::toMonetary($changeDue);

        $this->assertSame('0.01', $rounded);
    }
}
