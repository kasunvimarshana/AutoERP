<?php

declare(strict_types=1);

namespace Tests\Unit\Sales;

use DateTimeImmutable;
use Modules\Sales\Application\Support\SalesTaxCalculator;
use Modules\Tax\Domain\Entities\TaxRate;
use Modules\Tax\Domain\RepositoryInterfaces\TaxRateRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class SalesTaxCalculatorTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeTaxRate(
        string $rate,
        string $type = 'percentage',
        bool $isCompound = false,
        int $taxGroupId = 1,
    ): TaxRate {
        return new TaxRate(
            tenantId: 1,
            taxGroupId: $taxGroupId,
            name: 'Test Rate',
            rate: $rate,
            type: $type,
            accountId: null,
            isCompound: $isCompound,
            isActive: true,
            id: null,
        );
    }

    private function makeRepo(array $rates): TaxRateRepositoryInterface
    {
        $mock = $this->createMock(TaxRateRepositoryInterface::class);
        $mock->method('findActiveByGroup')->willReturn($rates);

        return $mock;
    }

    // -------------------------------------------------------------------------
    // Without repository — preserves existing tax_amount
    // -------------------------------------------------------------------------

    public function test_without_repository_preserves_payload_tax_amount(): void
    {
        $calculator = new SalesTaxCalculator(null);
        $lines = [
            ['line_total' => '100.000000', 'tax_amount' => '10.000000'],
        ];

        $result = $calculator->calculateForLines($lines, 1, new DateTimeImmutable);

        $this->assertSame('10.000000', $result['lines'][0]['tax_amount']);
        $this->assertSame('10.000000', $result['tax_total']);
    }

    public function test_without_repository_defaults_tax_amount_to_zero(): void
    {
        $calculator = new SalesTaxCalculator(null);
        $lines = [
            ['line_total' => '100.000000'],
        ];

        $result = $calculator->calculateForLines($lines, 1, new DateTimeImmutable);

        $this->assertSame('0.000000', $result['lines'][0]['tax_amount']);
        $this->assertSame('0.000000', $result['tax_total']);
    }

    // -------------------------------------------------------------------------
    // With repository — no tax_group_id on line
    // -------------------------------------------------------------------------

    public function test_line_without_tax_group_id_preserves_existing_tax_amount(): void
    {
        $repo = $this->makeRepo([]);
        $calculator = new SalesTaxCalculator($repo);

        $lines = [
            ['line_total' => '200.000000', 'tax_amount' => '20.000000'],
        ];

        $result = $calculator->calculateForLines($lines, 1, new DateTimeImmutable);

        $this->assertSame('20.000000', $result['lines'][0]['tax_amount']);
        $this->assertSame('20.000000', $result['tax_total']);
    }

    // -------------------------------------------------------------------------
    // Simple percentage rate
    // -------------------------------------------------------------------------

    public function test_single_percentage_rate(): void
    {
        $repo = $this->makeRepo([$this->makeTaxRate('10')]);
        $calculator = new SalesTaxCalculator($repo);

        $lines = [
            ['line_total' => '100.000000', 'tax_group_id' => 1],
        ];

        $result = $calculator->calculateForLines($lines, 1, new DateTimeImmutable);

        $this->assertSame('10.000000', $result['lines'][0]['tax_amount']);
        $this->assertSame('10.000000', $result['tax_total']);
    }

    // -------------------------------------------------------------------------
    // Multiple simple percentage rates (additive)
    // -------------------------------------------------------------------------

    public function test_multiple_simple_rates_are_additive(): void
    {
        $repo = $this->makeRepo([
            $this->makeTaxRate('10'),  // 10% of 100 = 10
            $this->makeTaxRate('5'),   //  5% of 100 =  5
        ]);
        $calculator = new SalesTaxCalculator($repo);

        $lines = [
            ['line_total' => '100.000000', 'tax_group_id' => 1],
        ];

        $result = $calculator->calculateForLines($lines, 1, new DateTimeImmutable);

        $this->assertSame('15.000000', $result['lines'][0]['tax_amount']);
        $this->assertSame('15.000000', $result['tax_total']);
    }

    // -------------------------------------------------------------------------
    // Fixed rate
    // -------------------------------------------------------------------------

    public function test_fixed_rate_adds_flat_amount(): void
    {
        $repo = $this->makeRepo([$this->makeTaxRate('5', 'fixed')]);
        $calculator = new SalesTaxCalculator($repo);

        $lines = [
            ['line_total' => '200.000000', 'tax_group_id' => 1],
        ];

        $result = $calculator->calculateForLines($lines, 1, new DateTimeImmutable);

        $this->assertSame('5.000000', $result['lines'][0]['tax_amount']);
    }

    // -------------------------------------------------------------------------
    // Compound rate
    // -------------------------------------------------------------------------

    public function test_compound_rate_applies_on_top_of_simple_tax(): void
    {
        // Simple: 10% of 100 = 10 → simple_tax = 10
        // Compound: 5% of (100 + 10) = 5.5
        // Total tax = 10 + 5.5 = 15.5
        $repo = $this->makeRepo([
            $this->makeTaxRate('10', 'percentage', false),
            $this->makeTaxRate('5', 'percentage', true),
        ]);
        $calculator = new SalesTaxCalculator($repo);

        $lines = [
            ['line_total' => '100.000000', 'tax_group_id' => 1],
        ];

        $result = $calculator->calculateForLines($lines, 1, new DateTimeImmutable);

        $this->assertSame('15.500000', $result['lines'][0]['tax_amount']);
        $this->assertSame('15.500000', $result['tax_total']);
    }

    // -------------------------------------------------------------------------
    // Multiple lines — tax_total is sum
    // -------------------------------------------------------------------------

    public function test_tax_total_sums_across_lines(): void
    {
        $repo = $this->makeRepo([$this->makeTaxRate('10')]);
        $calculator = new SalesTaxCalculator($repo);

        $lines = [
            ['line_total' => '100.000000', 'tax_group_id' => 1], // tax = 10
            ['line_total' => '200.000000', 'tax_group_id' => 1], // tax = 20
        ];

        $result = $calculator->calculateForLines($lines, 1, new DateTimeImmutable);

        $this->assertSame('10.000000', $result['lines'][0]['tax_amount']);
        $this->assertSame('20.000000', $result['lines'][1]['tax_amount']);
        $this->assertSame('30.000000', $result['tax_total']);
    }

    // -------------------------------------------------------------------------
    // Mixed lines — some with, some without tax_group_id
    // -------------------------------------------------------------------------

    public function test_mixed_lines_partial_tax_groups(): void
    {
        $repo = $this->makeRepo([$this->makeTaxRate('10')]);
        $calculator = new SalesTaxCalculator($repo);

        $lines = [
            ['line_total' => '100.000000', 'tax_group_id' => 1, 'tax_amount' => '0.000000'],  // computed: 10
            ['line_total' => '50.000000', 'tax_amount' => '7.500000'],                          // preserved: 7.5
        ];

        $result = $calculator->calculateForLines($lines, 1, new DateTimeImmutable);

        $this->assertSame('10.000000', $result['lines'][0]['tax_amount']);
        $this->assertSame('7.500000', $result['lines'][1]['tax_amount']);
        $this->assertSame('17.500000', $result['tax_total']);
    }

    // -------------------------------------------------------------------------
    // Zero-rate group returns zero tax
    // -------------------------------------------------------------------------

    public function test_zero_rate_produces_zero_tax(): void
    {
        $repo = $this->makeRepo([$this->makeTaxRate('0')]);
        $calculator = new SalesTaxCalculator($repo);

        $lines = [
            ['line_total' => '100.000000', 'tax_group_id' => 1],
        ];

        $result = $calculator->calculateForLines($lines, 1, new DateTimeImmutable);

        $this->assertSame('0.000000', $result['lines'][0]['tax_amount']);
        $this->assertSame('0.000000', $result['tax_total']);
    }

    // -------------------------------------------------------------------------
    // Empty group (no rates) returns zero tax
    // -------------------------------------------------------------------------

    public function test_empty_rate_group_produces_zero_tax(): void
    {
        $repo = $this->makeRepo([]);
        $calculator = new SalesTaxCalculator($repo);

        $lines = [
            ['line_total' => '100.000000', 'tax_group_id' => 42],
        ];

        $result = $calculator->calculateForLines($lines, 1, new DateTimeImmutable);

        $this->assertSame('0.000000', $result['lines'][0]['tax_amount']);
    }

    // -------------------------------------------------------------------------
    // Non-array entries in $lines are passed through unchanged
    // -------------------------------------------------------------------------

    public function test_non_array_line_entries_are_passed_through(): void
    {
        $calculator = new SalesTaxCalculator(null);

        $lines = ['not-an-array', ['line_total' => '50.000000', 'tax_amount' => '5.000000']];
        $result = $calculator->calculateForLines($lines, 1, new DateTimeImmutable);

        $this->assertSame('not-an-array', $result['lines'][0]);
        $this->assertSame('5.000000', $result['tax_total']);
    }
}
