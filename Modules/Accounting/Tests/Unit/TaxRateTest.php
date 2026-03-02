<?php

declare(strict_types=1);

namespace Modules\Accounting\Tests\Unit;

use Modules\Accounting\Domain\Entities\TaxRate;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the TaxRate entity's calculateTax method.
 * Validates that tax calculation uses BCMath (from PHP_POS reference).
 */
class TaxRateTest extends TestCase
{
    private function makeTaxRate(
        string $rate,
        string $type = 'percentage',
        bool $isActive = true,
        bool $isCompound = false,
    ): TaxRate {
        return new TaxRate(
            id: 1,
            tenantId: 1,
            name: 'Test Tax',
            rate: $rate,
            type: $type,
            isActive: $isActive,
            isCompound: $isCompound,
        );
    }

    // ── Percentage tax ────────────────────────────────────────────────────

    public function test_percentage_tax_on_100_at_10_percent(): void
    {
        $taxRate = $this->makeTaxRate('10', 'percentage');

        $tax = $taxRate->calculateTax('100.0000');

        $this->assertSame('10.0000', $tax);
    }

    public function test_percentage_tax_on_200_at_5_percent(): void
    {
        $taxRate = $this->makeTaxRate('5', 'percentage');

        $tax = $taxRate->calculateTax('200.0000');

        $this->assertSame('10.0000', $tax);
    }

    public function test_percentage_tax_zero_rate_yields_zero(): void
    {
        $taxRate = $this->makeTaxRate('0', 'percentage');

        $tax = $taxRate->calculateTax('500.0000');

        $this->assertSame('0.0000', $tax);
    }

    public function test_percentage_tax_fractional_amount(): void
    {
        // 15% of 66.6666 = 9.9999...9 → BCMath scale 4 = 9.9999
        $taxRate = $this->makeTaxRate('15', 'percentage');

        $tax = $taxRate->calculateTax('66.6666');

        $this->assertSame('9.9999', $tax);
    }

    // ── Fixed (flat) tax ─────────────────────────────────────────────────

    public function test_fixed_tax_returns_rate_regardless_of_amount(): void
    {
        $taxRate = $this->makeTaxRate('5.0000', 'fixed');

        $tax = $taxRate->calculateTax('1000.0000');

        $this->assertSame('5.0000', $tax);
    }

    public function test_fixed_tax_same_for_any_amount(): void
    {
        $taxRate = $this->makeTaxRate('2.5000', 'fixed');

        $this->assertSame($taxRate->calculateTax('10.0000'), $taxRate->calculateTax('9999.0000'));
    }

    // ── Entity accessors ─────────────────────────────────────────────────

    public function test_getters_return_correct_values(): void
    {
        $taxRate = new TaxRate(42, 7, 'GST', '18', 'percentage', true, false);

        $this->assertSame(42, $taxRate->getId());
        $this->assertSame(7, $taxRate->getTenantId());
        $this->assertSame('GST', $taxRate->getName());
        $this->assertSame('18', $taxRate->getRate());
        $this->assertSame('percentage', $taxRate->getType());
        $this->assertTrue($taxRate->isActive());
        $this->assertFalse($taxRate->isCompound());
    }

    public function test_compound_flag_is_readable(): void
    {
        $compound = $this->makeTaxRate('5', 'percentage', true, true);

        $this->assertTrue($compound->isCompound());
    }

    public function test_inactive_tax_rate_is_readable(): void
    {
        $inactive = $this->makeTaxRate('10', 'percentage', false);

        $this->assertFalse($inactive->isActive());
    }
}
