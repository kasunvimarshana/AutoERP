<?php

declare(strict_types=1);

namespace Modules\Sales\Tests\Unit;

use Modules\Sales\Domain\ValueObjects\SaleTotal;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the SaleTotal value object.
 * All financial arithmetic must use BCMath; no floating-point.
 */
class SaleTotalTest extends TestCase
{
    // ── Basic calculation ─────────────────────────────────────────────────

    public function test_calculate_sums_line_totals_correctly(): void
    {
        $lines = [
            ['line_total' => '10.0000'],
            ['line_total' => '5.0000'],
        ];

        $total = SaleTotal::calculate($lines);

        $this->assertSame('15.0000', $total->subtotal);
    }

    public function test_zero_discount_and_zero_tax_yields_subtotal_as_total(): void
    {
        $lines = [['line_total' => '100.0000']];

        $total = SaleTotal::calculate($lines, '0', '0');

        $this->assertSame('100.0000', $total->subtotal);
        $this->assertSame('0.0000', $total->discountAmount);
        $this->assertSame('0.0000', $total->taxAmount);
        $this->assertSame('100.0000', $total->total);
    }

    // ── Discount ─────────────────────────────────────────────────────────

    public function test_discount_is_deducted_from_subtotal(): void
    {
        $lines = [['line_total' => '200.0000']];

        // 10% discount on 200 = 20; total = 180
        $total = SaleTotal::calculate($lines, '10', '0');

        $this->assertSame('20.0000', $total->discountAmount);
        $this->assertSame('180.0000', $total->total);
    }

    public function test_100_percent_discount_yields_zero_total(): void
    {
        $lines = [['line_total' => '50.0000']];

        $total = SaleTotal::calculate($lines, '100', '0');

        $this->assertSame('50.0000', $total->discountAmount);
        $this->assertSame('0.0000', $total->total);
    }

    // ── Tax ──────────────────────────────────────────────────────────────

    public function test_tax_is_applied_after_discount(): void
    {
        $lines = [['line_total' => '100.0000']];

        // 10% discount → 90; 10% VAT on 90 = 9; total = 99
        $total = SaleTotal::calculate($lines, '10', '10');

        $this->assertSame('10.0000', $total->discountAmount);
        $this->assertSame('9.0000', $total->taxAmount);
        $this->assertSame('99.0000', $total->total);
    }

    public function test_tax_on_full_amount_when_no_discount(): void
    {
        $lines = [['line_total' => '200.0000']];

        // 5% VAT on 200 = 10; total = 210
        $total = SaleTotal::calculate($lines, '0', '5');

        $this->assertSame('0.0000', $total->discountAmount);
        $this->assertSame('10.0000', $total->taxAmount);
        $this->assertSame('210.0000', $total->total);
    }

    // ── Empty lines ───────────────────────────────────────────────────────

    public function test_empty_lines_yields_zero_total(): void
    {
        $total = SaleTotal::calculate([]);

        $this->assertSame('0.0000', $total->subtotal);
        $this->assertSame('0.0000', $total->total);
    }

    // ── BCMath precision ──────────────────────────────────────────────────

    public function test_calculation_uses_bcmath_scale_of_four(): void
    {
        // 3 lines each 1/3 → subtotal should not lose precision
        $lines = [
            ['line_total' => '0.3333'],
            ['line_total' => '0.3333'],
            ['line_total' => '0.3334'],
        ];

        $total = SaleTotal::calculate($lines, '0', '0');

        $this->assertSame('1.0000', $total->subtotal);
    }

    public function test_multiple_lines_are_summed(): void
    {
        $lines = [
            ['line_total' => '10.0000'],
            ['line_total' => '20.0000'],
            ['line_total' => '30.0000'],
        ];

        $total = SaleTotal::calculate($lines);

        $this->assertSame('60.0000', $total->subtotal);
    }
}
